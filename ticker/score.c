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
#include <errno.h>

#include <time.h>
#include <sys/time.h>

#include "ticker.h"

/******* scoreing routines **********/

typedef struct table_struct {
  unsigned int id;
  unsigned long long score;
} table;

table * get_score_table (MYSQL *mysql, const char *q)
{
  MYSQL_RES *res;
  MYSQL_ROW row;
  int num;
  table *ret = NULL, *table_ptr;
  
  res = do_query (mysql, q);

  if (res && (num = mysql_num_rows(res))) {
    ret = calloc (sizeof(table), num+1);
    assert (ret);

    table_ptr = ret;
    while ((row = mysql_fetch_row (res)) ) {
      table_ptr->id = atoi(row[0]);
      table_ptr->score = atoll(row[1]);
      table_ptr++;
    }

    check_error (mysql);
    mysql_free_result(res);
  }

  return ret;
}

void calc_score (MYSQL *mysql)
{
  MYSQL_RES *res;
  MYSQL_ROW row;

  char q_fleet[] = "SELECT planet_id,"\
    "CEILING(SUM(units.num * (uc.metal+uc.crystal+uc.eonium)/10)) " \
    "FROM fleet,units, unit_class as uc WHERE units.id=fleet.fleet_id " \
    "AND units.unit_id=uc.id GROUP BY planet_id";

  char q_pds[] ="SELECT planet_id,"\
    "CEILING(SUM(pds.num * (uc.metal+uc.crystal+uc.eonium)/10)) " \
    "FROM pds, unit_class as uc WHERE pds.pds_id=uc.id "\
    "GROUP BY planet_id";

  char q_scan[] = "SELECT planet_id, "\
    "CEILING(SUM(scan.num * (sc.metal+sc.crystal+sc.eonium)/10)) " \
    "FROM scan,scan_class as sc WHERE scan.wave_id=sc.id " \
    "GROUP BY planet_id";

  char q_rc[] = "SELECT planet_id, "\
    "CEILING(SUM((rc_class.metal+rc_class.crystal+rc_class.eonium)/10)) " \
    "FROM rc, rc_class WHERE rc.status=3 AND rc.rc_id=rc_class.id "\
    "GROUP BY planet_id";

  char update[256];
  char upd_fmt[] = "UPDATE planet set score=%llu+(metal+crystal+eonium)/100" \
    "+(metalroids+crystalroids+eoniumroids)*1500 WHERE id=%d";

  table *prc, *pscan, *ppds, *pfleet;
  table *pprc, *ppscan, *pppds, *ppfleet;
  struct timeval start;

  debug (1, "calc_score");
  gettimeofday (&start, NULL);

  ppfleet = pfleet = get_score_table (mysql, q_fleet);
  print_runtime (&start); 
  ppscan  = pscan  = get_score_table (mysql, q_scan );
  print_runtime (&start);
  pppds   = ppds   = get_score_table (mysql, q_pds  );
  print_runtime (&start);
  pprc    = prc    = get_score_table (mysql, q_rc   );
  print_runtime (&start);
  
  res = do_query (mysql,"SELECT id FROM planet ORDER BY id");
  debug (2, "calc_score selects");
  print_runtime (&start);

  if (res && mysql_num_rows(res)) {
/*
    do_query (mysql, "LOCK TABLES planet WRITE");
  */
    while ((row = mysql_fetch_row (res))) {
      unsigned long long score = 0;
      int id = atoi(row[0]);

      if (ppfleet && ppfleet->id == id) {
	score += ppfleet->score;
	ppfleet++;
      }

      if (ppscan && ppscan->id == id) {
	score += ppscan->score;
	ppscan++;
      }

      if (pppds && pppds->id == id) {
	score += pppds->score;
	pppds++;
      }

      if (pprc && pprc->id == id) {
	score += pprc->score;
	pprc++;
      }


      sprintf (update, upd_fmt, score, id);
      do_query (mysql, update);
    }
  /*
    do_query (mysql, "UNLOCK TABLES");
   */
    check_error (mysql);
    mysql_free_result(res);
  }
  debug (2, "calc_score end");
  print_runtime (&start); 
}
