/*
 * MyPHPpa ticker
 * Copyright (C) 2003, 2007 Jens Beyer
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

#include <stdio.h>
#include <stdlib.h>
#include <assert.h>
#include <string.h>

#include "ticker.h"

/********* update routines **********/

typedef struct rc_class_struct {
  unsigned int id;
  unsigned int block_id;
  unsigned int rc_id;
  unsigned int next_id;
  unsigned int next_alt_id;
  unsigned int type;
  char * name;
} rc_class;

rc_class **get_rc_class (MYSQL *mysql)
{
  MYSQL_RES *res;
  MYSQL_ROW row;
  
  int num, high_id = 0, i;
  rc_class **ret = NULL;
  rc_class *rcc = NULL, *rc_class_ptr;

  debug (1, "get_rc_class");

  res = do_query (mysql,"SELECT id, block_id, rc_id, name, type " \
		  "FROM rc_class ORDER by id ASC");

  if (res && (num = mysql_num_rows(res))) {
    rcc = calloc (sizeof(rc_class), num+1);
    assert (rcc);

    rc_class_ptr = rcc;
    while ((row = mysql_fetch_row (res)) ) {
      rc_class_ptr->id = atoi(row[0]);
      rc_class_ptr->block_id = atoi(row[1]);
      rc_class_ptr->rc_id = atoi(row[2]);
      rc_class_ptr->name = strdup (row[3]);
      rc_class_ptr->type = atoi(row[4]);

      if (rc_class_ptr->id > high_id) 
	high_id = rc_class_ptr->id;
      rc_class_ptr++;
    }

    rc_class_ptr->id = 0;

    check_error (mysql);
    mysql_free_result(res);

    /* 
     * fprintf (logfile, "Found high_id=%d\n",high_id);
     */

    ret = calloc (sizeof(rc_class *), high_id+1);
    assert (ret);
    for (i=0; i<num; i++) {
      /*
       * fprintf (logfile, "Assigning %d -> [%d]\n",i, (rcc+i)->id);
       */
      ret[(rcc+i)->id] = rcc+i;
    }
    for (i=0; i<num; i++) {
      /* 
       * fprintf (logfile, "Reverse %d -> %d : %d\n",
       *       i,  (rcc+i)->rc_id, (rcc+i)->id);
       */
      if ( (rcc+i)->rc_id && ret[(rcc+i)->rc_id]) {
        if ( ret[(rcc+i)->rc_id]->next_id != 0 )
          ret[(rcc+i)->rc_id]->next_alt_id = (rcc+i)->id;
        else
          ret[(rcc+i)->rc_id]->next_id = (rcc+i)->id;
      } 
/*
      if ( (rcc+i)->rc_id && ret[(rcc+i)->rc_id])
	ret[(rcc+i)->rc_id]->next_id = (rcc+i)->id;
*/
    }
  }

  return ret;
}

void update_build (MYSQL *mysql)
{
  MYSQL_RES *res;
  MYSQL_ROW row;

  char query[256];
  char *query_fmt;
  char *insert_fmt;
  
  debug (1, "update_build");

  /* scans */
  do_query (mysql,"UPDATE scan_build SET build_ticks=build_ticks-1"); 

  res = do_query (mysql, "SELECT planet_id, scan_id, num FROM scan_build " \
		  "WHERE build_ticks=0");

  if (res && mysql_num_rows(res)) {
    query_fmt = "UPDATE scan SET num=%s+num WHERE wave_id=%s AND planet_id=%s";
    insert_fmt = "INSERT into scan SET num=%s,wave_id=%s,planet_id=%s";

    while ((row = mysql_fetch_row (res))) {
      sprintf (query, query_fmt, row[2], row[1], row[0]);

      if (!do_query (mysql, query)) {
	sprintf (query, insert_fmt, row[2], row[1], row[0]);
	do_query (mysql, query);
      }
    }

    do_query (mysql,"DELETE FROM scan_build WHERE build_ticks=0");
  }
  check_error (mysql);
  mysql_free_result(res);


  /* PDS */
  do_query (mysql,"UPDATE pds_build SET build_ticks=build_ticks-1");

  res = do_query (mysql, "SELECT planet_id, pds_id, num FROM pds_build " \
		  "WHERE build_ticks=0");

  if (res && mysql_num_rows(res)) {
    query_fmt = "UPDATE pds SET num=%s+num WHERE pds_id=%s AND planet_id=%s";
    insert_fmt = "INSERT into pds SET num=%s,pds_id=%s,planet_id=%s";

    while ((row = mysql_fetch_row (res))) {
      sprintf (query, query_fmt, row[2], row[1], row[0]);

      if (!do_query (mysql, query)) {
	sprintf (query, insert_fmt, row[2], row[1], row[0]);
	do_query (mysql, query);
      }
    }

    do_query (mysql,"DELETE FROM pds_build WHERE build_ticks=0");
  }
  check_error (mysql);
  mysql_free_result(res);


  /* fleet */
  do_query (mysql,"UPDATE unit_build SET build_ticks=build_ticks-1");

  res = do_query (mysql, 
		  "SELECT unit_build.unit_id, unit_build.num, fleet.fleet_id "\
		  "FROM unit_build, fleet WHERE unit_build.build_ticks=0 " \
		  "AND unit_build.planet_id=fleet.planet_id AND fleet.num=0");

  if (res && mysql_num_rows(res)) {
    query_fmt = "INSERT INTO units SET unit_id=%s, num=%s, id=%s";
  
    while ((row = mysql_fetch_row (res))) {
      sprintf (query, query_fmt, row[0], row[1], row[2]);
      do_query (mysql, query);
    }

    do_query (mysql,"DELETE FROM unit_build WHERE build_ticks=0");
  }
  check_error (mysql);
  mysql_free_result(res);
}

void move_fleets (MYSQL *mysql)
{
  MYSQL_RES *res, *cres;
  MYSQL_ROW row, crow;

  char query[256];

  debug (1, "move_fleets");

  res = do_query (mysql,"SELECT target_id,type FROM fleet " \
		  "WHERE (type=0 or type=10) AND ticks=0 AND full_eta!=0");

  if (res && mysql_num_rows(res)) {
    char *target_fmt = "UPDATE planet SET has_%s=has_%s-1 WHERE id=%s";
    char *galaxy_fmt = "UPDATE planet SET gal_hostile=gal_hostile-1 " \
      "WHERE x=%d AND y=%d AND id!=%d";

    while ((row = mysql_fetch_row (res))) {

      if ( '0' == *row[1] ) {
	sprintf (query, target_fmt, "friendly", "friendly", row[0]);
	do_query (mysql, query);
      } else {
	unsigned int x, y;
      
	/* query coords of target_p */
	sprintf (query, "SELECT x, y FROM planet WHERE id=%s", row[0]);
	cres = do_query (mysql, query);
	crow = mysql_fetch_row (cres);
	x = atoi (crow[0]);
	y = atoi (crow[1]);
	mysql_free_result(cres);

	sprintf (query, galaxy_fmt, x, y, row[0]);  
	do_query (mysql, query);

	sprintf (query, target_fmt, "hostile", "hostile", row[0]);
	do_query (mysql, query);
      }
    }

    do_query (mysql,
	      "UPDATE fleet SET ticks=full_eta,full_eta=0,type=0, " \
	      "target_id=0 " \
	      "WHERE (type=0 or type=10) AND ticks=0 AND full_eta!=0");
  }

  do_query (mysql,"UPDATE fleet SET ticks=ticks-1 WHERE ticks!=0");

  check_error (mysql);
  mysql_free_result(res);
}

void update_research (MYSQL *mysql)
{
  MYSQL_RES *res;
  MYSQL_ROW row;

  rc_class **links;
  char query[256];
  char * text_fmt, text[128];

  char *update_fmt = "UPDATE rc SET status=%d WHERE rc_id=%d AND planet_id=%d";
  char *upd_spezial = "UPDATE rc SET status=%d " \
    "WHERE rc_id=%d AND status=0 AND planet_id=%d";

  char *insert_fmt = "INSERT DELAYED INTO news SET "\
    "planet_id=%d,date=NOW(),tick=%d,type=%d,text='%s'";
  char *news_fmt = "UPDATE planet set has_news=1 WHERE id=%d";

  char *upd_pds_fmt = "INSERT INTO pds (planet_id,pds_id,num) " \
    "SELECT %d,id,0 FROM unit_class AS uc WHERE uc.rc_id=%d AND uc.class=5";
  char *upd_scan_fmt = "INSERT INTO scan (planet_id,wave_id,num) " \
    "SELECT %d,id,0 FROM scan_class AS sc WHERE rc_id=%d";

  char *upd_sp_fmt = "UPDATE planet SET speed_modifier=%d WHERE id=%d";
  char *upd_res_fmt = "UPDATE planet SET " \
    "planet_m=%d+planet_m,planet_c=%d+planet_c, planet_e=%d+planet_e " \
    "WHERE id=%d";
  char *upd_roid_fmt = "UPDATE planet SET roid_modifier=roid_modifier+%d "\
    "WHERE id=%d";

  debug (1, "update_research 1");

  links = get_rc_class (mysql);

  do_query (mysql,"UPDATE rc_build SET build_ticks=build_ticks-1 " \
	    "WHERE build_ticks>0");

  res = do_query (mysql,"SELECT rc_id, planet_id " \
		  "FROM rc_build WHERE rc_build.build_ticks=0");

  if (res && mysql_num_rows(res)) {
    int type;

    while ((row = mysql_fetch_row (res))) {
      unsigned int id = atoi (row[0]); 
      unsigned int pid = atoi (row[1]); 

      fprintf (logfile, "update_research: id: %d  name: %s [%d]  pid: %d\n",
	       id, links[id]->name, links[id]->type, pid);

      /* blocked other / rc disabled */
      if ( links[id]->block_id ) {
	sprintf (query, update_fmt, -1, links[id]->block_id, pid);
	do_query (mysql, query);
      }
      
      /* type */
      if ( links[id]->type ) {
	text_fmt = "Construction of <b>%s</b> finished.";
	type = 4;
      } else {
	text_fmt = "Research of <b>%s</b> finished.";
	type = 3;
      }

      /* text for news */
      sprintf (text, text_fmt, links[id]->name);

      /* news */
      sprintf (query, insert_fmt, pid, mytick, type, text);
      do_query (mysql, query);

      /* has_news */
      sprintf (query, news_fmt, pid);
      do_query (mysql, query);

      /* rc done */
      sprintf (query, update_fmt, 3, id, pid);
      do_query (mysql, query);

      /* enabling next */
      if (links[id]->next_id) {
        sprintf (query, upd_spezial, 1, links[id]->next_id, pid);
        do_query (mysql, query);
        if (links[id]->next_alt_id) {
          sprintf (query, upd_spezial, 1, links[id]->next_alt_id, pid);
          do_query (mysql, query);
        }
      }

/*      if (links[id]->next_id) {
	sprintf (query, upd_spezial, 1, links[id]->next_id, pid);
	do_query (mysql, query);
      }
*/

      if (id>0 && id<=80) {
	/* enable some stuff */
      
	if (id < 18) {
	  /* mining */
	  int pm = 0, pc = 0, pe = 0;

	  switch (id) {
	  case 1: pm = 1500; break;
	  case 3: pc = 1500; break;
	  case 5: pe = 1500; break;
	  case 7: pm = 3000; break;
	  case 9: pc = 3000; break;
	  case 11: pe = 3000; break;
	  case 13: pm = 6000; break;
	  case 15: pc = 6000; break;
	  case 17: pe = 6000; break;
	  default: 
	    continue;
	  }

	  sprintf (query, upd_res_fmt, pm, pc, pe, pid);
	  do_query (mysql, query);

        } else if (id < 22) {
          /* roid resources */
          int roid_res = 0;

          switch(id) {
            case 19: roid_res = 500; break;
            case 21: roid_res = 250; break;
            default:
              continue;
          }

          sprintf (query, upd_roid_fmt, roid_res, pid);
          do_query (mysql, query);

	} else if (id < 40) {
	  /* speed */
	  int sp = (id - 29) / 2;
	  if (!links[id]->type) continue;

	  sprintf (query, upd_sp_fmt, sp, pid);
	  do_query (mysql, query);

	} else if (id < 60) {
	  /* new pds */
	  sprintf (query, upd_pds_fmt, pid, id);
	  do_query (mysql, query);

	} else if (id < 80) {
	  /* new scan */
	  sprintf (query, upd_scan_fmt, pid, id);
	  do_query (mysql, query);
	}
        /* market: 90, 91 */
      } /* id >0 && id < 100 */

    } /* per row */
  }

  check_error (mysql);
  mysql_free_result(res);

  debug (1, "update_research end");  
  /* done */
  do_query (mysql,"DELETE FROM rc_build WHERE build_ticks = 0");
}

void update_resources (MYSQL *mysql, int havoc)
{
  /*
   *  res_min_per_roid=250
   *  res_max_per_roid=350
   */
  debug( 1, "update_resources");
  /* new formula */
  /* "UPDATE planet SET metal=metal+planet_m+"
   * select if(metalroids<42, floor((351-metalroids)*metalroids),
   * floor(sqrt(metalroids)*2000)) as res,metalroids  from planet;
   */


  if (havoc) {
    do_query (mysql,"UPDATE planet SET metal=metal+planet_m+" \
	      "metalroids*greatest(350+1-metalroids,150), " \
	      "crystal=crystal+planet_c+" \
	      "crystalroids*greatest(350+1-crystalroids, 150), " \
	      "eonium=eonium+planet_e+" \
	      "eoniumroids*greatest(350+1-eoniumroids, 150) " \
	      "WHERE mode!=4");
  } else {
    do_query (mysql, "UPDATE planet SET metal=metal+planet_m+"  
	      "IF(metalroids<42, FLOOR((351-metalroids)*metalroids),"
	      "FLOOR(sqrt(metalroids)*(2000+roid_modifier))), " 
	      "crystal=crystal+planet_c+" 
	      "IF(crystalroids<42, FLOOR((351-crystalroids)*crystalroids),"  
	      "FLOOR(sqrt(crystalroids)*(2000+roid_modifier))), " 
	      "eonium=eonium+planet_e+" 
	      "IF(eoniumroids<42, FLOOR((351-eoniumroids)*eoniumroids), " 
	      "FLOOR(sqrt(eoniumroids)*(2000+roid_modifier))) "
	      "WHERE mode!=4");
  }
}

