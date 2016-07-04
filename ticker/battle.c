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
#include <math.h>

#include "ticker.h"
#include "battle.h"

/* 
 * #define DEBUG_BATTLE
 * #define DEBUG_CAP
 */
#define DEBUG_BATTLE
#define DEBUG_CAP

#define CLEANUP

unsigned int number_units;
unsigned int max_id;

unit_class *uc;
unit_class **uc_rid;

/*
 * Tabelle aller Einheiten
 */
unit_class * get_unit_class (MYSQL *mysql) 
{
  MYSQL_RES *res;
  MYSQL_ROW row;

  unit_class *puc, *luc;

  debug (4, "get_unit_class");

  res = do_query (mysql, "SELECT MAX(id) from unit_class");

  check_error (mysql);
  row = mysql_fetch_row (res);
  max_id = atoi(row[0]);
  mysql_free_result(res);

  uc_rid = (unit_class **) calloc (max_id+1, sizeof(unit_class *));
  assert (uc_rid);


  res = do_query (mysql,"SELECT id, name, type, class, init, agility, " \
		  "weapon_speed, guns, power, armor, resistance, t1, t2, t3, " \
		  "metal, crystal, eonium FROM unit_class ORDER by id");

  number_units = mysql_num_rows (res);

  puc = malloc (sizeof(unit_class) * (number_units+1));
  assert (puc);

  luc = puc;
  while ((row = mysql_fetch_row (res))) {

    luc->id = atoi(row[0]);
    luc->name = strdup(row[1]);
    luc->type = atoi(row[2]);
    luc->class = atoi(row[3]);
    luc->init = atoi(row[4]);
    luc->agility = atoi(row[5]);
    luc->weapon_speed = atoi(row[6]);
    luc->guns = atoi(row[7]);
    luc->power = atoi(row[8]);
    luc->armor = atoi(row[9]);
    luc->resistance = atoi(row[10]);
    luc->t1 = atoi(row[11]);
    luc->t2 = (row[12] ? atoi(row[12]): 0);
    luc->t3 = (row[13] ? atoi(row[13]): 0);
    luc->metal = atoi(row[14]);
    luc->crystal = atoi(row[15]);
    luc->eonium = atoi(row[16]);

    uc_rid[luc->id] = luc;

#ifdef DEBUG_EXTREME
    fprintf (logfile, "[%d]: %s (%d, %d, %d...)\n", 
             luc->id, luc->name, luc->type, luc->class, luc->init);
      fflush(logfile);
#endif
    luc++;
  }
  luc->name = NULL; /* last one */
  luc->init = (unsigned int) -1;

  check_error (mysql);
  mysql_free_result(res);

  return puc;
}

/*
 * Hilfsroutinen
 */
void free_fleet (fleet *f, int flag) 
{
  if (f) {
    free (f->res);
    free (f->rid);
    free (f->units);

    memset (f, 0, sizeof(fleet));
    if (!flag)
      free (f);
  }
}

fleet *malloc_fleet (fleet *f, unsigned int size)
{
  fleet *r;

  if (!f)
    r = (fleet *) calloc (1, sizeof(fleet));
  else
    r = f;

  assert (r);

  r->num_units = 0;
  r->res   = (resource *) calloc (1, sizeof(resource)); 
  r->rid   = (unit **) calloc (max_id+1, sizeof(unit *));
  r->units = (unit *) calloc (size, sizeof(unit));

  assert (r->units);

  return r;
}

void free_planet (planet *p)
{
  int i;
  if (p) {
    for (i=0; i<MAX_FLEETS; i++)
      free_fleet (p->f+i, 1);

    if (p->pds)
      free_fleet (p->pds, 0);

    free_fleet (p->total_fleet, 0);

    free (p->lost);
    memset (p, 0, sizeof(planet));
    free (p);
  }
}

planet *malloc_planet (planet *p, unsigned int size)
{
  planet *r;
  int i;

  if (!p)
    r = (planet *) calloc (1, sizeof(planet));
  else
    r = p;

  assert (r);

  for (i=0; i<MAX_FLEETS; i++)
    malloc_fleet(r->f+i, size);

  r->lost = (resource *) calloc (1, sizeof(resource));
      
  return r;
}

void calc_fleetpoints ( fleet *f )
{
  int i;
  for (i=0; i<f->num_units; i++) {
    f->res->metal += f->units[i].num * f->units[i].u->metal;
    f->res->crystal += f->units[i].num * f->units[i].u->crystal;
    f->res->eonium += f->units[i].num * f->units[i].u->eonium;
  }
  f->res->total = f->res->metal + f->res->crystal 
    + f->res->eonium;
}

/* 
 * Alle einheiten des angegriffenen Planeten
 */
planet *make_planet_defense (MYSQL *mysql, unsigned int planet_id)
{
  static char query_fmt[] = "SELECT sum(units.num), units.unit_id, " \
    "unit_class.init, fleet.num, fleet.fleet_id FROM units,fleet,unit_class " \
    "WHERE units.id=fleet.fleet_id AND fleet.type=0 " \
    "AND fleet.target_id=0 AND units.unit_id=unit_class.id " \
    "AND fleet.planet_id=%d " \
    "AND fleet.full_eta=0 AND fleet.ticks=0 " \
    "GROUP BY fleet.num, units.unit_id ";

  static char pds_fmt[] = "SELECT pds.num, pds.pds_id, unit_class.init " \
    "FROM pds, unit_class "\
    "WHERE pds.planet_id=%d AND pds.pds_id=unit_class.id " \
    "AND pds.num>0 GROUP BY pds.pds_id";

  static char score_fmt[] = "SELECT score, metalroids, crystalroids, " \
    "eoniumroids, uniniroids FROM planet WHERE id=%d";

  /* clean up code */
  static char del_fmt[] = "DELETE FROM units WHERE id=%d AND unit_id=%d";
  static char ins_fmt[] = "INSERT INTO units SET id=%d,num=%d,unit_id=%d";

  MYSQL_RES *res;
  MYSQL_ROW row;

  char query[512], qdel[128], qins[128];
  unsigned int num_unit = 0, num_pds = 0, num;
  unsigned int id, fleet_id;
  int i, j, n, fn;

  fleet  *f = NULL;
  planet *p = NULL;
  
  debug (4, "make_planet_defense");

  sprintf (query, query_fmt, planet_id);
  res = do_query (mysql, query);

  check_error (mysql);
  num_unit = mysql_num_rows(res);

  p = malloc_planet(NULL, num_unit);
  p->planet_id = planet_id;

  /* we have some own fleet to defend ? */
  if (num_unit) {
    while ((row = mysql_fetch_row (res))) {
      num = atoi(row[0]);
      id = atoi(row[1]);
      fn = atoi(row[3]);
      fleet_id = atoi(row[4]);
    
      n = p->f[fn].num_units++;

      /* clean up units */
      sprintf (qdel, del_fmt, fleet_id, id);
      sprintf (qins, ins_fmt, fleet_id, num, id);
      do_query (mysql, qdel);
      do_query (mysql, qins);

      p->f[fn].units[n].num = num;
      p->f[fn].units[n].id = id;
      p->f[fn].units[n].init = atoi(row[2]);
      p->f[fn].fleet_id = fleet_id;
      p->f[fn].rid[id] = p->f[fn].units + n;
    }

    check_error (mysql);
  }
  mysql_free_result(res);

  /* do we have any pds */
  sprintf (query, pds_fmt, planet_id);
  res = do_query (mysql, query);

  if (res && (num_pds = mysql_num_rows(res))) {

    p->pds = malloc_fleet (NULL, num_pds);
    p->pds->num_units = num_pds;

    j = 0;
    while ((row = mysql_fetch_row (res))) {
      id = atoi(row[1]);
      p->pds->rid[id] = p->pds->units + j;
      p->pds->units[j].num = atoi(row[0]);
      p->pds->units[j].id = id;
      p->pds->units[j++].init = atoi(row[2]);
    }

    check_error (mysql);
    mysql_free_result(res);
  }
  
  /* count roids and score */
  sprintf (query, score_fmt, planet_id);
  res = do_query (mysql, query);

  if (res && mysql_num_rows(res)) {
    /* expect a single row */
    row = mysql_fetch_row (res);

    p->planet_score = atol(row[0]);
    for (j=0; j<4; j++) {
      p->cap_roids[j] = 0;
      p->roids[j] = atoi(row[j+1]);
    }
    p->cap_roids[4] = 0; /* hold sum */

  }
  check_error (mysql);
  mysql_free_result(res);
  
  /* gather total fleet */

  p->total_fleet = f = malloc_fleet (NULL, num_unit+num_pds);

  for (i=0; i<MAX_FLEETS; i++)
    for (j=0; j<p->f[i].num_units; j++) {

      id = p->f[i].units[j].id;

      if (f->rid[id]) {
	f->rid[id]->num += p->f[i].units[j].num;
      } else {
	n = f->num_units++;
	f->units[n].num  = p->f[i].units[j].num;
	f->units[n].init = p->f[i].units[j].init;
	f->units[n].id   = id;
	f->units[n].u    = uc_rid[id];
	f->rid[id] = f->units + n;
      }
    }

  /* add pds to total */
  for (j=0; j<num_pds; j++) {
    n = f->num_units++;
    id = p->pds->units[j].id;
    f->units[n].num  = p->pds->units[j].num;
    f->units[n].init = p->pds->units[j].init;
    f->units[n].id   = id;
    f->units[n].u    = uc_rid[id];
    f->rid[id] = f->units + n;
  }

  calc_fleetpoints (f);

  return p;
}

/*
 * get att/def fleet sent by planet_id to target_id
 */
planet *make_planet_fleet (MYSQL *mysql, int type, unsigned int planet_id, 
			   unsigned int target_id)
{
  static char query_fmt[] = "SELECT sum(units.num), units.unit_id, " \
    "unit_class.init, fleet.num, fleet.fleet_id FROM units,fleet,unit_class " \
    "WHERE units.id=fleet.fleet_id AND (fleet.type>9)=%d " \
    "AND fleet.target_id=%d AND units.unit_id=unit_class.id " \
    "AND fleet.planet_id=%d " \
    "AND fleet.full_eta!=0 AND fleet.ticks=0 " \
    "GROUP BY fleet.num, units.unit_id ";

  /* clean up code */
  static char del_fmt[] = "DELETE FROM units WHERE id=%d AND unit_id=%d";
  static char ins_fmt[] = "INSERT INTO units SET id=%d,num=%d,unit_id=%d";
  
  MYSQL_RES *res;
  MYSQL_ROW row;

  char query[512], qdel[128], qins[128];

  planet *p = NULL;
  fleet  *f = NULL;
  unsigned int num_unit = 0;
  unsigned int id, fleet_id;
  int i, j, num, n, fn;

  debug (4, "make_planet_fleet");

  sprintf (query, query_fmt, type, target_id, planet_id);
  res = do_query (mysql, query);

  if (res && (num_unit = mysql_num_rows(res))) {

    p = malloc_planet (NULL, num_unit);
    p->planet_id = planet_id;

    for (j=0; j<4; j++) {
      p->cap_roids[j] = 0;
      p->roids[j] = 0;
    }
    p->cap_roids[4] = 0; /* holds sum */ 

    while ((row = mysql_fetch_row (res))) {
      num = atoi(row[0]);
      id = atoi(row[1]);
      fn = atoi(row[3]);
      fleet_id = atoi(row[4]);

      n = p->f[fn].num_units++;

      /* clean up units */
      sprintf (qdel, del_fmt, fleet_id, id);
      sprintf (qins, ins_fmt, fleet_id, num, id);
      do_query (mysql, qdel);
      do_query (mysql, qins);

      p->f[fn].units[n].num = num;
      p->f[fn].units[n].id = id;
      p->f[fn].units[n].init = atoi(row[2]);
      p->f[fn].fleet_id = fleet_id;
      p->f[fn].rid[id] = p->f[fn].units + n;
    }

    /* gather total fleet */

    p->total_fleet = f = malloc_fleet (NULL, num_unit);
    
    for (i=0; i<MAX_FLEETS; i++)
      for (j=0; j<p->f[i].num_units; j++) {

	id = p->f[i].units[j].id;

	id = p->f[i].units[j].id;

	if (f->rid[id]) {
	  f->rid[id]->num += p->f[i].units[j].num;
	} else {
	  n = f->num_units++;
	  f->units[n].num  = p->f[i].units[j].num;
	  f->units[n].init = p->f[i].units[j].init;
	  f->units[n].id   = id;
	  f->units[n].u    = uc_rid[id];
	  f->rid[id] = f->units + n;
	}
      }
    calc_fleetpoints (f);
  }

  check_error (mysql);
  mysql_free_result(res);

  return p;
}

/*
   * get total fleet of attacking and defending forces
   */
fleet * make_fleet (MYSQL *mysql, int type, unsigned int target_id, 
		    planet ***p_ptr, planet **target_planet)
{
  MYSQL_RES *res;
  MYSQL_ROW row;

  char query[256];
  fleet * f = NULL;
  planet **p = NULL;
  unsigned int num_planets = 0;
  int i = 0;

  char * query_fmt="SELECT fleet.planet_id FROM fleet WHERE fleet.target_id=%d "\
    "AND (fleet.type>9)=%d AND fleet.full_eta!=0 AND fleet.ticks=0 "\
    "GROUP BY fleet.planet_id";

  debug (3, "make_fleet");
 
  sprintf (query, query_fmt, target_id, type);
  res = do_query (mysql, query);

  if (res && (num_planets = mysql_num_rows(res))) {

    if (!type) num_planets++;
    p = (planet **) calloc (num_planets+1, sizeof(planet *));
    
    assert(p);

    while ((row = mysql_fetch_row(res))) {
      // collect planet forces
      p[i++] = make_planet_fleet (mysql, type, (unsigned int) atoi(row[0]), 
				  target_id);
    }
    
  } else {
    if ( !type ) {
      /* attacked planet only */
      num_planets = 1;
      p = (planet **) calloc (num_planets+1, sizeof(planet *));
    }
  }

  check_error (mysql);
  mysql_free_result(res);

  if (p) {

    /* if def get defending planet */
    if ( !type ) {
      p[i] = make_planet_defense (mysql, target_id);
      *target_planet = p[i++];
    }
    p[i] = NULL; /* mark last one */

    f = malloc_fleet (NULL, number_units);

    /* summate into *f */
    for (i=0; i<num_planets; i++) {

      if (!p[i]->total_fleet) {
	fprintf (logfile, "should never happen ? - no fleet for id [%d]\n", 
		 p[i]->planet_id);
        fflush(logfile);
      } else {
	int j, n;

	for (j=0; j<p[i]->total_fleet->num_units; j++) {

	  unsigned int id = p[i]->total_fleet->units[j].id;
	  if (f->rid[id]) {
	    f->rid[id]->num += p[i]->total_fleet->units[j].num;
	  } else {
	    n = f->num_units++;
	    f->units[n].id = p[i]->total_fleet->units[j].id;
	    f->units[n].num = p[i]->total_fleet->units[j].num;
	    f->units[n].init = p[i]->total_fleet->units[j].init;

	    /* class ptr */
	    f->units[n].u = uc_rid[id];

	    /* reverse id */
	    f->rid[id] = f->units + n;
	  }
	}
      }
    }

    calc_fleetpoints (f);
  }

  *p_ptr = p;
  return f;
  
}

/* 
 * Last part of battlecode 
 * Hit resolution
 */
unsigned int resolve_avg_shots (unsigned int firing, 
				unit_class * att, unit* def)
{
  if (!firing) return 0;

  switch (att->class) 
    {
    case CLASS_EMP: {
      float to_stun_one;
      unsigned int to_stun_all;

      if (def->u->resistance != 100)
	to_stun_one = 100. / (100. - def->u->resistance);
      else
	return 0;

      to_stun_all = ceil( def->targeted * to_stun_one );

#ifdef DEBUG_BATTLE
      fprintf(logfile, "              --res emp: f: %d tsa %d\n",
	      firing, to_stun_all);
      fflush(logfile);
#endif

      if (to_stun_all < firing) {
	def->to_be_stunned += def->targeted;
	
	return to_stun_all;
      } else {
	unsigned int diff;
	diff = floor (firing / to_stun_one);

	def->to_be_stunned += diff;

	return firing;
      }

      break;
    }
    case CLASS_CAP: {
      fprintf (logfile, "resolve_avg_shots CLASS_CAP\n");
      fflush(logfile);
      break;
    }
    default: {
      float hit_chance;
      unsigned int to_kill_one, to_kill_all, to_kill_first, hits_to_kill_one;

      hit_chance = (25. + att->weapon_speed - def->u->agility)/100.;
#ifdef DEBUG_BATTLE
      fprintf(logfile, 
	      "              --res att: hit: %f (%d  /  %d)\n",
	      hit_chance, att->weapon_speed,def->u->agility);
      fflush(logfile);
#endif

      if (hit_chance > 0.) {
	to_kill_first = ceil ((def->u->armor - def->hits) /
			      (att->power * hit_chance));
	hits_to_kill_one = ceil (def->u->armor / ((float) att->power));
	to_kill_one = hits_to_kill_one / hit_chance;
	to_kill_all = ceil (to_kill_first + (def->targeted - 1) * to_kill_one);

      } else {
	return firing;
      }
#ifdef DEBUG_BATTLE
      fprintf(logfile, 
	      "              --res att: f: %d (%f) htko %d tko %d tka %d\n",
	      firing, hit_chance, hits_to_kill_one, to_kill_one, to_kill_all);
      fflush(logfile);
#endif

      if (to_kill_all  < firing) {
      
	def->to_be_killed += def->targeted;

	return to_kill_all;
      } else if ( to_kill_first > firing) {
      
	def->hits += floor (firing * att->power * hit_chance);

	return firing;
      } else {
	unsigned int diff;
	
	diff = 1 + floor ((firing - to_kill_first) / ((float)to_kill_one));
	def->to_be_killed += diff;
	
	if ( att->power * ( (firing - to_kill_first) % to_kill_one) 
	     < def->u->armor)
	  def->hits = att->power * ( (firing - to_kill_first) % to_kill_one);
	else
	  def->hits = 0;

	return firing;
      }

      break;
    }
    }

  /* should not happen */
  fprintf (logfile, "Reaching bad code line\n");
      fflush(logfile);
  return firing;
}

/*
 * Divide firepower between equivalent targets
 */
int attack_targets (unit_class *att, 
		    fleet *def_fleet,
		    unsigned int current_target_type,
		    unsigned int *guns_left)
{
  unit *def;
  unsigned int num_targets = 0, firing_on;
  unsigned int shots_used = 0;

  int i;

#ifdef DEBUG_BATTLE
  fprintf (logfile, "Attack_targets (%s -> %d, guns: %d)\n",
	   att->name, current_target_type, *guns_left);
      fflush(logfile);
#endif

  for (i=0; i< def_fleet->num_units; i++) {
    def = def_fleet->units+i;

    def->targeted = 0;
    if ( def->u->type == current_target_type 
	|| current_target_type == 255) {

      switch (att->class) 
	{
	case CLASS_EMP:
	  /* don't emp PDS */
	  if ( 0 < (def->num - def->killed - def->stunned 
		    - def->to_be_stunned - def->capping) &&
	       def->u->resistance != 100) {
	    def->targeted = def->num - def->killed  - def->stunned 
	      - def->to_be_stunned;
	    num_targets += def->targeted;

#ifdef DEBUG_BATTLE
	    fprintf (logfile, "              EMP: target %d "\
		     "(num: %d k: %d s: %d tbs: %d\n", 
		     def->targeted, num_targets,
		     def->killed, def->stunned, def->to_be_stunned); 
      fflush(logfile);
#endif
	  }
	  break;

	case CLASS_CAP:
	  fprintf (logfile, "attack_targets: CLASS_CAP\n");
      fflush(logfile);
	  break;

        case CLASS_MISSILE: 
          if ( def->u->class == CLASS_PDS &&
               0 < (def->num - def->killed - def->to_be_killed - def->capping)) {
            def->targeted = def->num - def->killed 
                            - def->to_be_killed - def->capping;
            num_targets += def->targeted;

#ifdef DEBUG_BATTLE
            fprintf (logfile, "         MISS ATT: target %d "\
                              "(num: %d k: %d s: %d tbs: %d\n", 
                              def->targeted, num_targets,
                              def->killed, def->stunned, def->to_be_stunned); 
      fflush(logfile);
#endif
          }
          break;

	default:
	  if ( 0 < (def->num - def->killed - def->to_be_killed - def->capping)) {
	    def->targeted = def->num - def->killed 
	      - def->to_be_killed - def->capping;
	    num_targets += def->targeted;

#ifdef DEBUG_BATTLE
	    fprintf (logfile, "              ATT: target %d "\
		     "(num: %d k: %d s: %d tbs: %d\n", 
		     def->targeted, num_targets,
		     def->killed, def->stunned, def->to_be_stunned); 
      fflush(logfile);
#endif
	  }
	  break;
	}      
    }
  }

  if (!num_targets) 
    return 1;
  
  for (i=0; i< def_fleet->num_units; i++) {
    def = def_fleet->units+i;

    if (def->targeted && def->num && shots_used < *guns_left) {
      firing_on = ceil(*guns_left * ((float)def->targeted /(float)num_targets));
      shots_used += resolve_avg_shots (firing_on, att, def);
    }
  }

#ifdef DEBUG_BATTLE
  fprintf(logfile, "              Used: shots %d  guns_left %d\n",
	  shots_used, *guns_left);
      fflush(logfile);
#endif

  if (shots_used > *guns_left)
    shots_used = *guns_left;
  *guns_left -= shots_used;

  /* should be ==0 */
  return (*guns_left==0);
 /* return (*guns_left!=0); */
}

/*
 * select targets by init
 * handles battle cap
 */
void act_initiative (unit *cur, fleet *def, 
		     planet *target_p, 
		     unsigned long long networth, unsigned long long att_score) 
{
  int survivor;
  
  survivor = cur->num - cur->killed - cur->stunned - cur->capping;

  if ( survivor > 0) {
    unsigned int guns_left;
    int done = 0;

    guns_left = survivor * cur->u->guns;

#ifdef DEBUG_BATTLE
    fprintf (logfile, 
	     "[%d] act_init (%d/%d/%d) guns: %d, survivor: %d, (%s: guns %d)\n",
	     cur->u->init, cur->num, cur->killed, cur->stunned,
	     guns_left, survivor, cur->u->name, cur->u->guns);
      fflush(logfile);
#endif

    if (cur->u->class == CLASS_CAP) {
      /* extra case for 'spezial' */
      debug (4, "act_initiative CLASS_CAP");
      
      /* if def do nothing */
      if (target_p != NULL ) {

	float roid_chance, oroid_chance;
	unsigned int max_grab_total = 0, max_grab[4];
	int i;

	/* if attack capture roids */
	oroid_chance = (target_p->planet_score / ((double) networth ));
	roid_chance = (target_p->planet_score / 
		       ((double) networth + 2*att_score) );

#ifdef DEBUG_CAP
	fprintf (logfile, "CAP ----- New: %2.2f, old %2.2f\n", 
		 roid_chance, oroid_chance);
	fprintf (logfile, "CAP ----- Pscore: %llu\tFleet: %llu\tAscore: %llu\n", 
		 target_p->planet_score, networth, att_score);
                 fflush(logfile);
#endif
	if (roid_chance > 0.15) {
	  roid_chance = 0.15;
	}

	for (i=0; i<4; i++) {
	  max_grab[i] = 0;

	  if (target_p->roids[i] > 6) {
	    max_grab[i] = floor (target_p->roids[i] * roid_chance);

	    if (target_p->roids[i] - max_grab[i] < 6) {
	      max_grab[i] = target_p->roids[i] - 6;
	    }

	    max_grab_total += max_grab[i];

	    fprintf (logfile, "grabbing type %d: %d -> %d total\n", 
		     i, max_grab[i], max_grab_total);
            fflush(logfile);
	  } else {
	    fprintf (logfile, "%d .. roids too low\n", target_p->roids[i]);
            fflush(logfile);
	  }
	}

	fprintf (logfile, "CAP [pid: %d] pods %d roidmax %d chance %f\n",
		 target_p->planet_id, 
		 survivor,
		 max_grab_total, 
		 roid_chance);
        fflush(logfile);

	if (survivor > max_grab_total) {
	  /* mehr pods als roids moeglich */
	  for (i=0; i<4; i++) {
	    target_p->cap_roids[i] += max_grab[i];
	    target_p->cap_roids[4] += max_grab[i];
	    fprintf (logfile, "now: [%d] %d total %d\n",
		     i, target_p->cap_roids[i], target_p->cap_roids[4]);
            fflush(logfile);
	  }
	  cur->capping += target_p->cap_roids[4];
	  if (max_grab_total != target_p->cap_roids[4]) {
	    fprintf (logfile, "mismatch: cap: %d max: %d\n",
		     target_p->cap_roids[4], max_grab_total);
            fflush(logfile);
	  }
	} else {
	  /* zuwenig pods */
	  double capped;

	  cur->capping += survivor;
	  capped = survivor / (double) max_grab_total;

	  for (i=0; i<4; i++) {
	    int dif = rint (capped * max_grab[i]);
/* if (dif+target_p->cap_roids[4] > capped) dif--; */ 
	    if (dif < 0) dif = 0;
	    target_p->cap_roids[i] += dif;
	    target_p->cap_roids[4] += dif;

	    fprintf (logfile, "now: [%d] %d / %d total %d\n", 
		     i, target_p->cap_roids[i], dif, target_p->cap_roids[4]);
            fflush(logfile);
	  }

	  if (target_p->cap_roids[4] < survivor) {
	    int dif = survivor-target_p->cap_roids[4];
	    fprintf (logfile, "Fixing rounding error: pod %d roid %d\n",
		     survivor, target_p->cap_roids[4]);
      fflush(logfile);
	    /* muesste reichen */
	    for (i=0; i<4 && dif!=0; i++) {
	      if (max_grab[i] > target_p->cap_roids[i]) {
		dif--;
		target_p->cap_roids[i] ++;
		target_p->cap_roids[4] ++;
		fprintf (logfile, "fix: [%d] %d (%d) total %d\n", 
			 i, target_p->cap_roids[i], dif, target_p->cap_roids[4]);
      fflush(logfile);
	      }
	    }
	  }

	}
      }
#ifdef DEBUG_CAP
      else {
	fprintf (logfile, "Cant defend with Cap....\n");
      fflush(logfile);
      }
#endif
    } else {

      /* primary target */
      while (!done && guns_left && cur->u->t1) {
#ifdef DEBUG_BATTLE
	fprintf (logfile, "prim: guns %d %s: t1: %d\n",
		 guns_left, cur->u->name, cur->u->t1 );
      fflush(logfile);
#endif
	done = attack_targets ( cur->u, def, cur->u->t1, &guns_left);
      }
      done = 0;

      /* secondary target */
      while (!done && guns_left && cur->u->t2) {
#ifdef DEBUG_BATTLE
	fprintf (logfile, "sec : guns %d %s: t2: %d\n",
		 guns_left, cur->u->name, cur->u->t2 );
      fflush(logfile);
#endif
	done = attack_targets ( cur->u, def, cur->u->t2, &guns_left);
      }
      done = 0;

      /* whats left after that */
      while (!done && guns_left && cur->u->t3) {
#ifdef DEBUG_BATTLE
	fprintf (logfile, "tri : guns %d %s: t3 %d\n",
		 guns_left, cur->u->name, cur->u->t3 );
      fflush(logfile);
#endif
	done = attack_targets ( cur->u, def, cur->u->t3, &guns_left);
      }

      if (cur->u->class == CLASS_MISSILE && target_p != NULL)
        cur->to_be_killed = cur->num;
    }
  }
}

void clean_up_attack (fleet *f) 
{
  int i;

  debug (6, "clean_up_attack");

  for (i=0; i<f->num_units; i++) {
    if (f->units[i].num && 
	(f->units[i].to_be_killed || f->units[i].to_be_stunned)) {

#ifdef DEBUG_BATTLE
      fprintf( logfile, "[%s %d] num %d, tbk %d (%d)  tbs %d (%d)\n",
	       f->units[i].u->name, i, f->units[i].num, 
	       f->units[i].to_be_killed, f->units[i].killed,
	       f->units[i].to_be_stunned, f->units[i].stunned);
      fflush(logfile);
#endif

      f->units[i].killed += f->units[i].to_be_killed;
      f->units[i].stunned += f->units[i].to_be_stunned;

      /* rounding errors */
      if (f->units[i].killed > f->units[i].num) {
#ifdef DEBUG_BATTLE
	fprintf( logfile, "rounding kills: %d > %d\n", 
		 f->units[i].killed, f->units[i].num);
      fflush(logfile);
#endif
	f->units[i].killed = f->units[i].num;
      }
      if (f->units[i].stunned > f->units[i].num){
#ifdef DEBUG_BATTLE
	fprintf( logfile, "rounding stunned: %d > %d\n", 
		 f->units[i].stunned, f->units[i].num);
      fflush(logfile);
#endif
	f->units[i].stunned = f->units[i].num;
      }
      if (f->units[i].capping && 
	  (f->units[i].capping >  f->units[i].num 
	   - f->units[i].stunned -f->units[i].killed) ) {
	f->units[i].capping =  f->units[i].num 
	  - f->units[i].stunned -f->units[i].killed;
      }
    }

    f->units[i].to_be_killed = 0;
    f->units[i].to_be_stunned = 0;
    f->units[i].targeted = 0;
    /* sollte das so sein ?
       f->units[i].hits = 0;
    */
  }
}

void remove_roids (MYSQL *mysql, planet* p, int *lcap)
{
  static char *upd_fmt = "UPDATE planet set metalroids=metalroids-%d,"\
    "crystalroids=crystalroids-%d,eoniumroids=eoniumroids-%d,"\
    "uniniroids=uniniroids-%d WHERE id=%d";

  static char *news_fmt = "INSERT INTO news SET planet_id=%d, " \
    "date=now(), tick=%d, type=1, text='<table class=report border=1 width=100%%>" \
    "<tr class=report><th colspan=4 align=left>Asteroid Lost</th></tr>"\
    "<tr><th>&nbsp;</th><th>Total</th><th>Lost</th></tr>%s</table>'";
  
  char query[1024], dummy[256], table[512];
  int i;

  debug (4, "Remove roids");

  memset(table, 0, 512);

  sprintf (query, upd_fmt, lcap[0], lcap[1], lcap[2], lcap[3], p->planet_id);
  do_query (mysql, query);
#ifdef DEBUG_CAP
  fprintf (logfile, "%s\n", query);
      fflush(logfile);
#endif
  
  for (i=0; i<4; i++) {
    if (lcap[i]) {
      char * name = NULL;

      switch (i) {
      case 0: name="Metal"; break;
      case 1: name="Crystal"; break;
      case 2: name="Eonium"; break;
      case 3: name="Uninitialized"; break;
      }
      sprintf (dummy, "<tr><td>%s</td><td>%d</td><td>%d</td></tr>",
	       name, p->roids[i], lcap[i]);
      strcat (table, dummy);
    }
  }

  sprintf (query, news_fmt, p->planet_id, mytick, table);
  do_query (mysql, query);
#ifdef DEBUG_CAP_X
  fprintf (logfile, "%s\n", query);
      fflush(logfile);
#endif
  
}

void add_roids (MYSQL *mysql, planet* p, int *lcap, planet *target_p)
{
  static char *upd_fmt = "UPDATE planet set metalroids=metalroids+%d,"\
    "crystalroids=crystalroids+%d,eoniumroids=eoniumroids+%d,"\
    "uniniroids=uniniroids+%d,has_news=1 WHERE id=%d";

  static char *news_fmt = "INSERT INTO news SET planet_id=%d, " \
    "date=now(), tick=%d, type=1, text='<table class=report border=1 width=100%%>" \
    "<tr class=report><th colspan=4 align=left>Asteroid Captured</th></tr>"\
    "<tr><th>&nbsp;</th><th>Total</th>" \
    "<th>Captured</th><th>Yours</th></tr>%s</table>'";

  char query[1024], dummy[256], table[512];
  int i;

  debug (4, "Add roids");

  memset(table, 0, 512);

  if ( p->cap_roids[4] ) {
    sprintf (query, upd_fmt, p->cap_roids[0], p->cap_roids[1], 
	     p->cap_roids[2], p->cap_roids[3], p->planet_id);
    do_query (mysql, query);
#ifdef DEBUG_CAP_X
    fprintf (logfile, "%s\n", query);
      fflush(logfile);
#endif

    for (i=0; i<4; i++) {
      if (p->cap_roids[i]) {
	char * name = NULL;

	switch (i) {
	case 0: name="Metal"; break;
	case 1: name="Crystal"; break;
	case 2: name="Eonium"; break;
	case 3: name="Uninitialized"; break;
	}
	sprintf (dummy, "<tr><td>%s</td><td>%d</td><td>%d</td><td>%d</td></tr>",
		 name, target_p->roids[i], lcap[i], p->cap_roids[i]);
	strcat (table, dummy);
      }
    }
    /* here: total of capped cap_roids[4] */

    sprintf (query, news_fmt, p->planet_id, mytick, table);
    do_query (mysql, query);

#ifdef DEBUG_CAP_X
    fprintf (logfile, "%s\n", query);
      fflush(logfile);
#endif
  }
}

unsigned long long dv_networth (planet **p)
{
  unsigned long long total_networth = 0;
  planet **p_ptr = p;

  do {
    fleet * pf = (*p_ptr)->total_fleet;

    if (pf->rid[CAP_UNIT] &&  
	(pf->rid[CAP_UNIT]->num - pf->rid[CAP_UNIT]->killed 
	 - pf->rid[CAP_UNIT]->stunned - pf->rid[CAP_UNIT]->capping ) > 0) {

      (*p_ptr)->planet_score = pf->res->total;
      total_networth += pf->res->total;

    } else {
      (*p_ptr)->planet_score = 0;
    }

  } while (*(++p_ptr));

  fprintf (logfile, "dv_networth: %llu\n", total_networth);
      fflush(logfile);

  return total_networth;
}

void divide_roids_helper (planet **p, planet *target_p, 
                          unsigned long long *networth)
{
  int i = 0, may_get, cnt;
  double ratio, nratio;
  planet **p_ptr;
  unsigned int tcap_roids[5];

  debug (4, "divide_roids_helper");

  for (i=0; i<=4; i++) {
    tcap_roids[i] = target_p->cap_roids[i];
  }

  p_ptr = p;

  do {
    fleet *pf = (*p_ptr)->total_fleet;

    if ( (*p_ptr)->planet_score ) {
      unit *u = pf->rid[CAP_UNIT];

      ratio = (double) (*p_ptr)->planet_score / (double) *networth;
      may_get = rint(ratio * target_p->cap_roids[4]);

      if (may_get > target_p->cap_roids[4]) {
	fprintf (logfile, "may_get %d too high: %d\n",
		 may_get, target_p->cap_roids[4]);
        fflush(logfile);
	may_get = target_p->cap_roids[4];
      }

      if (may_get >= (u->num - u->killed - u->stunned - u->capping)) {
	fprintf (logfile, "may_get %d more/equal then  pods: %d (c: %d)\n",
		 may_get,  u->num - u->killed - u->stunned - u->capping, 
                 u->capping);
        fflush(logfile);

	(*p_ptr)->planet_score = 0;
  	may_get = u->num - u->killed - u->stunned - u->capping;
      }
      may_get = (may_get > 0 ? may_get : 0); 
      
      if (may_get > 0) {
	/**/
        if (may_get != target_p->cap_roids[4])
          nratio = may_get / (double) target_p->cap_roids[4];
	else
	  nratio = 1.0;

        fprintf (logfile, "Correct: %.3f -> %.3f / %d (%llu, %llu)\n",
		 ratio, nratio, may_get,
		 (*p_ptr)->planet_score, *networth);
        fflush(logfile);

	for (i=0; i<4; i++) {
	  int tget;
 
	  if (nratio == 1.0)
	    tget =  target_p->cap_roids[i];
          else 
            tget  = rint( nratio * target_p->cap_roids[i] );
	  
          while (tget > 0 && ((may_get-tget)<0 || tget > tcap_roids[i])) {
            fprintf (logfile, 
                     "Fixing rounding error: tget %d, mg %d tr %d\n",
                     tget, may_get,tcap_roids[i]);
            fflush(logfile);
            tget--;
          }
          if (tget < 0) {
            fprintf (logfile, "Fixing negative capture: %d -> %d\n",
                     tcap_roids[i], tget);
            fflush (logfile);
            tget = 0;
            target_p->cap_roids[i] = 0;
          }
 
	  (*p_ptr)->cap_roids[i] += tget;
	  tcap_roids[i] -= tget;
	  may_get -= tget;

	  /* sum */
	  tcap_roids[4] -= tget;
	  (*p_ptr)->cap_roids[4] += tget;

	  fprintf (logfile, "{%d} -> %d |%d - %d|(mg: %d, tot: %d - %d)\n",
		   i, tget, (*p_ptr)->cap_roids[i], tcap_roids[i],
		   may_get, (*p_ptr)->cap_roids[4], tcap_roids[4]);
          fflush(logfile);
	}

      }

      /* fix rounding errors */
      if (may_get != 0) {
	fprintf (logfile, "fix rounding error: may_get = %d\n",
		 may_get);
        fflush(logfile);
        if (may_get > 0) {
	  for (i=0; i<4 && may_get>0; i++) {
	    while (((int)tcap_roids[i]) > 0 && may_get>0) {
	      
	      (*p_ptr)->cap_roids[i] += 1;
	      tcap_roids[i] -= 1;
	      may_get--;

	      /* sum */
	      tcap_roids[4] -= 1;
	      (*p_ptr)->cap_roids[4] += 1;

	      fprintf (logfile, " -> adding {%d} -> %d (rest %d)\n",
	   	       i, (*p_ptr)->cap_roids[i], may_get);
      fflush(logfile);
	    }
	  }
        } else {
          for (i=0; i<4 && may_get<0; i++) {
            if ((*p_ptr)->cap_roids[i] > 0) {
              (*p_ptr)->cap_roids[i] -= 1;
              tcap_roids[i] += 1;
              may_get++;

              /* sum */
              tcap_roids[4] += 1;
              (*p_ptr)->cap_roids[4] -= 1;

              fprintf (logfile, " -> removing {%d} -> %d (rest %d)\n",
                       i, (*p_ptr)->cap_roids[i], may_get);
      fflush(logfile);
            }
          }
        }
      }

      /**/
      
      fprintf (logfile, 
	       "[id %d] may get %d (==0) (max %d), ->>" \
	       " did get %d left %d (score %llu, ratio %f)\n", 
	       (*p_ptr)->planet_id, may_get, 
	       u->num - u->killed - u->stunned - u->capping, 
	       (*p_ptr)->cap_roids[4],  tcap_roids[4],
	       (*p_ptr)->planet_score, ratio);
      fflush(logfile);

      for (i=0, cnt=0; i<4; i++) {
	cnt += (*p_ptr)->cap_roids[i];
      }

      if (cnt != (*p_ptr)->cap_roids[4]) {
	fprintf (logfile, "AAAARGH cnt=%d cap=%d\n",
		 cnt, (*p_ptr)->cap_roids[4]);
      fflush(logfile);
      }

      // BUG: bei zweiten/3. durchlauf!!
      // u->capping += (*p_ptr)->cap_roids[4];
      u->capping = (*p_ptr)->cap_roids[4];

    }  
  } while (*(++p_ptr));

  for (i=0; i<=4; i++) {
    target_p->cap_roids[i] = tcap_roids[i];
  }
}

void divide_roids (MYSQL* mysql, fleet *f, planet **p, planet *target_p)
{
  int i, n;
  unsigned long long total_networth = 0;
  planet **p_ptr;
  int *lcap_roids = NULL;
  unsigned int safety_cap_roids;

  debug (3, "divide_roids");

  /* Verteilung:
     - gibt es roids ?
     - wer hat pods ?
     - wieviel networth hat welche fleet ?
     
     Roids je % networth und genuegend pods
  */

  if (target_p->cap_roids[4] == 0) {
#ifdef DEBUG_CAP
    fprintf (logfile, "No Roids captured\n");
      fflush(logfile);
#endif
    return;
  }
  /* secure rounding errors */
  if (target_p->cap_roids[4] != f->rid[CAP_UNIT]->capping) {
    int diff_r = target_p->cap_roids[4] - f->rid[CAP_UNIT]->capping;

    fprintf (logfile, "Diff Pods (%d) cappin then roids (%d) capped!\n",
	     f->rid[CAP_UNIT]->capping, target_p->cap_roids[4]);
      fflush(logfile);
    target_p->cap_roids[4] = f->rid[CAP_UNIT]->capping;

    if (diff_r > 0) {
      if (target_p->cap_roids[0] > diff_r) {
	target_p->cap_roids[0] -= diff_r; 
	diff_r = 0; 
      }
      if (target_p->cap_roids[1] > diff_r) {
	target_p->cap_roids[1] -= diff_r; 
	diff_r = 0; 
      }
      if (target_p->cap_roids[2] > diff_r) {
	target_p->cap_roids[2] -= diff_r; 
	diff_r = 0; 
      }
      if (target_p->cap_roids[3] >= diff_r) {
	target_p->cap_roids[2] -= diff_r; 
	diff_r = 0; 
      }
      if (diff_r>0) return;
    } else {
      target_p->cap_roids[4] += diff_r;
      f->rid[CAP_UNIT]->capping += diff_r;
    }
  }

  /* fuer remove */
  lcap_roids = malloc (4*sizeof(int));
  for (i=0; i<4; i++) {
    lcap_roids[i] = target_p->cap_roids[i];
  }

#ifdef DEBUG_CAP
  fprintf (logfile, "%d Roids captured\n", target_p->cap_roids[4]);
      fflush(logfile);
#endif

  total_networth = dv_networth (p);

  /* ugly bugfix against endless loops */
  safety_cap_roids = target_p->cap_roids[4];

  while (target_p->cap_roids[4]>0 && total_networth) {

#ifdef DEBUG_CAP
    fprintf (logfile, "Rest roids %d (%llu)\n", 
             target_p->cap_roids[4], total_networth);
      fflush(logfile);
#endif

    divide_roids_helper (p, target_p, &total_networth);

    if (safety_cap_roids == target_p->cap_roids[4] 
	|| ((int)target_p->cap_roids[4]) <0) {
      fprintf (logfile, "Emergency exit: could not devide rest roids: %d\n",
               safety_cap_roids);
      fflush(logfile);
      target_p->cap_roids[4] = 0;
    } else {
      safety_cap_roids = target_p->cap_roids[4];
    }
    total_networth = dv_networth (p);
  }

  for (i=0; i<4; i++) {
    if (target_p->cap_roids[i]) {
      fprintf (logfile, "Missed Roid [%d] %d\n",i, target_p->cap_roids[i]);
      fflush(logfile);
      target_p->cap_roids[i] = 0;
    }
  }

  /* remove roids */
  remove_roids (mysql, target_p, lcap_roids);
  
  p_ptr = p;
  do {
    fleet * pf = (*p_ptr)->total_fleet;

    if (pf->rid[CAP_UNIT] && pf->rid[CAP_UNIT]->capping > 0) {

      pf->rid[CAP_UNIT]->killed += pf->rid[CAP_UNIT]->capping;
      add_roids (mysql, (*p_ptr), lcap_roids, target_p);

      /* in wich fleet did he loose */
      for (n=0; n<MAX_FLEETS; n++) {

	if ( (*p_ptr)->f[n].rid[CAP_UNIT] ) {
	  unit *u = (*p_ptr)->f[n].rid[CAP_UNIT];
	
	  if (u->num == pf->rid[CAP_UNIT]->num)
	    u->killed += pf->rid[CAP_UNIT]->capping;
	  else {
	    float lratio = ((float) u->num) / ((float) pf->rid[CAP_UNIT]->num);
	    u->killed += rint (lratio * pf->rid[CAP_UNIT]->capping);
	  }

#ifdef DEBUG_CAP
	  fprintf (logfile, "       [%d] %d (=%d) -> k: (%d tot) %d\n",
		   (*p_ptr)->planet_id, u->id, CAP_UNIT, 
		   u->num,u->killed);
      fflush(logfile);
#endif
          if (u->killed > u->num) {
            fprintf (logfile, "Emergency correct: Killed %d, num %d\n",
              u->killed , u->num); 
      fflush(logfile);
            u->killed = u->num;
          }
	}
      }
    }
  } while (*(++p_ptr));

  /* set for total fleet */
  if ( f->rid[CAP_UNIT] && f->rid[CAP_UNIT]->capping > 0) {
    f->rid[CAP_UNIT]->killed += f->rid[CAP_UNIT]->capping;
  }

#ifdef CLEANUP
  free (lcap_roids);
#endif /* CLEANUP */
} 

void divide_damage (fleet *f, planet **p, resource *del) 
{
  float *percent_stunned;
  float *percent_killed;

  planet **p_ptr;
  int n, i;

  debug (3, "divide_damage");
  percent_stunned = (float *) calloc (max_id+1, sizeof(float));
  percent_killed  = (float *) calloc (max_id+1, sizeof(float));

  for (i=0; i<f->num_units; i++) {
    unsigned int id = f->units[i].id;

    if (f->units[i].num) {

      if (del != NULL && f->units[i].u->class != CLASS_MISSILE) {
        del->metal   += f->units[i].killed * f->units[i].u->metal;
        del->crystal += f->units[i].killed * f->units[i].u->crystal;
        del->eonium  += f->units[i].killed * f->units[i].u->eonium;
      }

      if (f->units[i].killed >= f->units[i].num) {
	/* all killed */
	percent_killed[id] = 1.;
      } else if (!f->units[i].killed) {
	/* none killed */
	percent_killed[id] = 0.;
      } else {
	percent_killed[id] = 
	  ((float) f->units[i].killed) / ((float) f->units[i].num);
      }

      if (f->units[i].stunned >= f->units[i].num) {
	/* all stunned */
	percent_stunned[id] = 1.;
      } else if (!f->units[i].stunned) {
	/* none stunned */
	percent_stunned[id] = 0.;
      } else {
	percent_stunned[id] = 
	  ((float) f->units[i].stunned) / ((float) f->units[i].num);
      }

#ifdef DEBUG_BATTLE
      fprintf (logfile, "       [all] %d | %d -> k: %2.3f s: %2.3f\n",
	       id, f->units[i].num,
	       percent_killed[id],
	       percent_stunned[id]);
      fflush(logfile);
#endif
    } else {
      fprintf (logfile, "Should not happen..no ships in fleet id [%d] %d\n",
               f->units[i].id, i);
      fflush(logfile);
    }
  }

  if (del != NULL) {
    del->total = del->metal + del->crystal + del->eonium;
  }

  p_ptr = p;
  do {
    fleet * pf = (*p_ptr)->total_fleet;

    /* what did this planet loose */ 
    for (i=0; i<f->num_units; i++) {
      int id = f->units[i].id;
      float num;

      /* no units of this type in fleet */
      if (!pf->rid[id]) continue;

      if (f->units[i].u->class != CLASS_PDS) {

	if ((num = pf->rid[id]->num)) {

	  /* percent of total fleet */
	  pf->rid[id]->killed  = rint(num * percent_killed[id]);
	  pf->rid[id]->stunned = rint(num * percent_stunned[id]);

          if (f->units[i].u->class != CLASS_MISSILE) {
            (*p_ptr)->lost->metal   += pf->rid[id]->killed * f->units[i].u->metal;
            (*p_ptr)->lost->crystal += pf->rid[id]->killed * f->units[i].u->crystal;
            (*p_ptr)->lost->eonium  += pf->rid[id]->killed * f->units[i].u->eonium;
	  }
  
#ifdef DEBUG_BATTLE
	  fprintf (logfile, 
		   "       [%d] %d -> k: %d (%2.2f) s: %d (%2.2f) | %d\n",
		   (*p_ptr)->planet_id, id, 
		   pf->rid[id]->killed,
		   percent_killed[id],
		   pf->rid[id]->stunned,
		   percent_stunned[id],
		   (int) num);
      fflush(logfile);
#endif
	}
      } else {
	fleet *pds = (*p_ptr)->pds;
	unit *u = pds->rid[id];

	/* extra for pds: we dont need to divide */
	if (percent_killed[id] == 1.)
	  pf->rid[id]->killed =  pf->rid[id]->num;
	else
	  pf->rid[id]->killed = f->units[i].killed;

        (*p_ptr)->lost->metal   += pf->rid[id]->killed * f->units[i].u->metal;
        (*p_ptr)->lost->crystal += pf->rid[id]->killed * f->units[i].u->crystal;
        (*p_ptr)->lost->eonium  += pf->rid[id]->killed * f->units[i].u->eonium;
	    
	u->killed = pf->rid[id]->killed;

	/* (*p_ptr)->pds->rid[id]->killed = pf->rid[id]->killed; */
      }
    }

    /* in wich fleet did he loose */
    for (n=0; n<MAX_FLEETS; n++) {

      for (i=0; i<(*p_ptr)->f[n].num_units; i++) {
	unit *u = (*p_ptr)->f[n].units + i;
	unsigned int id = u->id;

	if (pf->rid[id]->killed) {
	  if (u->num == pf->rid[id]->num)
	    u->killed = pf->rid[id]->killed;
	  else {
	    float lratio = ((float) u->num) / ((float) pf->rid[id]->num);
	    u->killed = rint (lratio * pf->rid[id]->killed);
	  }
#ifdef DEBUG_BATTLE
	  fprintf (logfile, "       [%d] %d (=%d) -> k: %d\n",
		   (*p_ptr)->planet_id, i, id, 
		   u->killed);
      fflush(logfile);
#endif
	}
      }
    }

  } while (*(++p_ptr));

  free (percent_killed);
  free (percent_stunned);

}

/* 
   */
void send_bnews (MYSQL *mysql, char *fmt[], planet **p, char *head)
{
  static char ins_fmt[] = "INSERT INTO news SET planet_id=%d, " \
    "date=now(), tick=%d, type=1, text='" \
    "<table class=report border=1 width=100%%>"\
    "<tr><th align=left colspan=10 class=report>%s</th></tr>"\
    "<tr><th>&nbsp;</th><th colspan=3>Attackers </th>" \
    "<th colspan=3>Defenders </th><th colspan=3>Yours </th><tr>" \
    "<tr><td>&nbsp;</td><td>Total </td><td>Killed </td><td>Stunned </td>" \
    "<td>Total </td><td>Killed </td><td>Stunned </td>" \
    "<td>Total </td><td>Killed </td><td>Stunned </td><tr>\n"
    "%s</table>'";

  static char upd_fmt[] = "UPDATE planet SET has_news=1 WHERE id=%d";

  static char dummy[512];
  static char table[8192];
  char *query;

  planet **p_ptr;
  int i;

  debug (5, "send_bnews");

  query = malloc (8512);
  assert (query);

  p_ptr = p;
  do {
    unsigned int mynum=0, mykill=0, mystun=0;

    memset (table, 0, 8192);
    memset (query, 0, 8512);

    for (i=0; i<number_units; i++) {
      if (fmt[i][0]) {
	int id = uc[i].id;

	if ((*p_ptr)->total_fleet->rid[id]) {
	  sprintf (dummy, fmt[i], 
		   (*p_ptr)->total_fleet->rid[id]->num,
		   (*p_ptr)->total_fleet->rid[id]->killed,
		   (*p_ptr)->total_fleet->rid[id]->stunned);

          mynum  += (*p_ptr)->total_fleet->rid[id]->num;
          mykill += (*p_ptr)->total_fleet->rid[id]->killed;
          mystun += (*p_ptr)->total_fleet->rid[id]->stunned;
	} else {
	  sprintf (dummy, fmt[i], 0, 0, 0);
	}
	strcat (table, dummy);
      }
    }
    sprintf (dummy, fmt[number_units], mynum, mykill, mystun);
    strcat (table, dummy);

#ifdef DEBUG_BATTLE_EXT
    fprintf (logfile, "Table length: %d\n",strlen(table));
    fprintf (logfile, table);
      fflush(logfile);
#endif

    sprintf (query, ins_fmt, (*p_ptr)->planet_id, mytick, head, table);
    do_query (mysql, query);

    sprintf (dummy, upd_fmt, (*p_ptr)->planet_id);
    do_query (mysql, dummy);

  } while (*(++p_ptr));

#ifdef CLEANUP
  free (query);
#endif /* CLEANUP */
} 

void battle_news (MYSQL *mysql, fleet *att, fleet *def,
		  planet **att_p, planet **def_p, planet *target_p) 
{
  MYSQL_RES *res;
  MYSQL_ROW row;

  char **stmp;
  char head[256];

  int i;
  unsigned int anum=0, akill=0, astun=0, dnum=0, dkill=0, dstun=0;

  debug (4, "battle_news");

  stmp = (char **) malloc ((number_units+1)*sizeof(char *));

  for (i=0; i<=number_units; i++) {
    stmp[i] = (char *) malloc (256);
    memset (stmp[i], 0, 256);
  }

  for (i=0; i<number_units; i++) {
    int id = uc[i].id;

    unit *au = att->rid[id];
    unit *du = def->rid[id];

    if (au) {
      if (du) {
	sprintf (stmp[i], "<tr><td>%s </td>" \
		 "<td>%d </td><td>%d </td><td>%d </td>" \
		 "<td>%d </td><td>%d </td><td>%d  </td>" \
		 "<td>%%d </td><td>%%d </td><td>%%d </td></tr>\n",
		 au->u->name,
		 au->num,
		 au->killed,
		 au->stunned,
		 du->num,
		 du->killed,
		 du->stunned);

        anum  += au->num;
        akill += au->killed;
        astun += au->stunned;
        dnum  += du->num;
        dkill += du->killed;
        dstun += du->stunned;
      } else {
	sprintf (stmp[i], "<tr><td>%s </td>" \
		 "<td>%d </td><td>%d </td><td>%d </td>" \
		 "<td>0 </td><td>0 </td><td>0 </td>" \
		 "<td>%%d </td><td>%%d </td><td>%%d </td></tr>\n",
		 au->u->name,
		 au->num,
		 au->killed,
		 au->stunned);

        anum  += au->num;
        akill += au->killed;
        astun += au->stunned;
      }
    } else if (du) {
      sprintf (stmp[i], "<tr><td>%s </td>" \
	       "<td>0 </td><td>0 </td><td>0 </td>" \
	       "<td>%d </td><td>%d </td><td>%d </td>" \
	       "<td>%%d </td><td>%%d </td><td>%%d </td></tr>\n",
	       du->u->name,
	       du->num,
	       du->killed,
	       du->stunned);

      dnum  += du->num;
      dkill += du->killed;
      dstun += du->stunned;
    }
  }

  sprintf (stmp[number_units], "<tr><td><b>Total</b> </td>" \
           "<td>%u </td><td>%u </td><td>%u </td>" \
           "<td>%u </td><td>%u </td><td>%u  </td>" \
           "<td>%%u </td><td>%%u </td><td>%%u </td></tr>\n",
           anum, akill, astun, dnum, dkill, dstun);

  /* find target name /coord for news */
  /* 
  sprintf (head, "SELECT planetname, x, y, z FROM planet WHERE id=%d", 
	   target_p->planet_id);
  */
  sprintf (head, "SELECT REPLACE(planetname,\"'\",\"\\\\'\"), x, y, z FROM planet WHERE id=%d", 
	   target_p->planet_id);
  res = do_query (mysql, head);

  if (res && mysql_num_rows(res)) {
    row = mysql_fetch_row (res);
    sprintf (head, "Battlereport from %s [%s:%s:%s]",
	     row[0], row[1], row[2], row[3]);
  } else {
    sprintf (head, "Battlereport");
  }
  check_error (mysql);
  mysql_free_result(res);

  /* ok finally write 'm */
  send_bnews (mysql, stmp, att_p, head);
  send_bnews (mysql, stmp, def_p, head);

  for (i=0; i<number_units; i++) {
    free (stmp[i]);
  }
  free (stmp);

}

/*
 * clean fleets with no ships left
 */
void clear_empty_fleets (MYSQL *mysql, planet **p, 
			 planet * target_p, int type)
{
  static char upd_fmt[] = "UPDATE fleet SET target_id=0, type=0, "\
    "full_eta=0, ticks=0 WHERE planet_id=%u AND fleet_id=%u";

  static char hos_fmt[] = 
    "UPDATE planet SET has_hostile=has_hostile-1 WHERE id=%d";
  static char fre_fmt[] = 
    "UPDATE planet SET has_friendly=has_friendly-1 WHERE id=%d";
  static char gal_fmt[] = 
    "UPDATE planet SET gal_hostile=gal_hostile-1 WHERE x=%d AND y=%d "\
    "AND id!=%d";
  /*
  static char fre_fmt[] = 
    "UPDATE planet SET has_friendly=has_friendly-1 WHERE x=%d AND y=%d";
  */
  char query[256];

  MYSQL_RES *res;
  MYSQL_ROW row;

  int i, n, found;
  planet **p_ptr = p;
  unsigned int target_id = target_p->planet_id;
  unsigned int x, y;

  debug (3, "clean_empty_fleet");

  /* query coords of target_p */
  sprintf (query, "SELECT x, y FROM planet WHERE id=%d", target_id);
  res = do_query (mysql, query);
  row = mysql_fetch_row (res);
  x = atoi (row[0]);
  y = atoi (row[1]);
  
  check_error (mysql);
  mysql_free_result(res);

  do {
    for (n=0; n<MAX_FLEETS; n++) {

      if ((*p_ptr)->f[n].num_units) {

	for (i=0, found=0; i<(*p_ptr)->f[n].num_units; i++) {
	  if ((*p_ptr)->f[n].units[i].num) {
	    found = 1;
	    break;
	  }
	}

	if (!found) {
	  /* no ships in fleet left */

	  fprintf (logfile, "clearing hostile type %d pid %d [%d,%d]=(%d)",
		   type, (*p_ptr)->planet_id, x, y, target_id);
      fflush(logfile);

	  sprintf (query, upd_fmt, (*p_ptr)->planet_id, 
		   (*p_ptr)->f[n].fleet_id);
	  do_query (mysql, query);

	  if (type || (*p_ptr)->planet_id != target_id) {
	    if (type) {
	      sprintf (query, hos_fmt, target_id);
	      do_query (mysql, query);

	      sprintf (query, gal_fmt, x, y, target_id);
	    } else {
	      sprintf (query, fre_fmt, target_id);
	      /* sprintf (query, fre_fmt, x, y); */
	    }
	    do_query (mysql, query);
	  }
	}
      }
    }

  } while (*(++p_ptr));
}

void update_db_pds (MYSQL *mysql, planet *p)
{
  static char upd_fmt[] = "UPDATE pds SET num=%u " \
    "WHERE planet_id=%d AND pds_id=%d";
  char query[128];

  int n;

  debug (3, "update_db_pds");
  
  if (p->pds) {
    for (n=0; n < p->pds->num_units; n++)
      if (p->pds->units[n].killed) {
	sprintf (query, upd_fmt,
		 p->pds->units[n].num - p->pds->units[n].killed,
		 p->planet_id, p->pds->units[n].id);
	do_query (mysql, query);
      }
  }	
}

/* 
 * update fleets in db according to planet info 
 */
void update_db (MYSQL *mysql, planet **p)
{
  static char upd_fmt[] = "UPDATE units SET num=%u WHERE id=%d AND unit_id=%d";

  char query[256];
  int i, n, num;
  planet **p_ptr = p;

  debug (3, "update_db");

  do {
    for (n=0; n<MAX_FLEETS; n++) {
      for (i=0; i<(*p_ptr)->f[n].num_units; i++) {
	unit *u = (*p_ptr)->f[n].units + i;

	if (u->num && u->killed) {
	  num = u->num - u->killed;

#ifdef DEBUG_BATTLE
	  fprintf (logfile, "[planet:%d] id:%d fleet:%d rest:%d (was:%d)\n",
		   (*p_ptr)->planet_id, u->id, n, num, u->num);
      fflush(logfile);
#endif
	  sprintf (query, upd_fmt, 
		   num, (*p_ptr)->f[n].fleet_id, u->id);
	  do_query (mysql, query);
	  
	  if (!num)
	    u->num = 0;
	}
      }
    }
  } while (*(++p_ptr));
}

/* 
 * salvage if defending
 */
void salvage_db (MYSQL *mysql, planet **p, 
		 resource *def, resource *del, int havoc)
{
  static char upd_fmt[] = "UPDATE planet SET metal=metal+%llu, "\
    "crystal=crystal+%llu,eonium=eonium+%llu WHERE id=%d";

  static char news_fmt[] ="INSERT INTO news set date=NOW(),type=2,"\
    "planet_id=%d, tick=%d, text='You received a salvage of %llu metal, %llu crystal and %llu eonium.'";

  char query[256];
  planet **p_ptr = p;
  double ratio;
  unsigned long long m, c, e;

  debug (3, "salvage_db");

  if (!del->metal && !del->crystal && !del->eonium)
    return;
  del->metal   /= 4;
  del->crystal /= 4;
  del->eonium  /= 4;

  do {
    (*p_ptr)->lost->total =
      (*p_ptr)->lost->metal +
      (*p_ptr)->lost->crystal +
      (*p_ptr)->lost->eonium;

    if ( def->total > 0 && (havoc || (*p_ptr)->lost->total > 0)  ) {

      ratio = ((double) (*p_ptr)->total_fleet->res->total) 
        / ((double) def->total);

      m = rint(ratio * del->metal);
      c = rint(ratio * del->crystal);
      e = rint(ratio * del->eonium);

      if ( !havoc && (m + c + e)  >  ((*p_ptr)->lost->total)*2 ) {
        double corr = (2 * (double) (*p_ptr)->lost->total )
          / ((double) (m + c + e) );

        fprintf (logfile, "Salvage too high: %llu > 2 * %llu\n",
                 m+c+e, (*p_ptr)->lost->total);
      fflush(logfile);

        m = rint(corr * m);
        c = rint(corr * c);
        e = rint(corr * e);

        fprintf (logfile, "Salvage too high: reduced to %llu\n",
                 (unsigned long long) (m+c+e));
      fflush(logfile);
      }

      sprintf (query, upd_fmt, m, c, e, (*p_ptr)->planet_id);
      do_query (mysql, query);

      sprintf (query, news_fmt,(*p_ptr)->planet_id,  mytick, m, c, e);
      do_query (mysql, query);

    }
  } while (*(++p_ptr));
}

/* summate all planets score */
unsigned long long sum_planet_score (MYSQL *mysql, planet **p)
{
  static char score_fmt[] = "SELECT score FROM planet WHERE id=%d";

  MYSQL_RES *res;
  MYSQL_ROW row;

  char query[128];
  unsigned long long ret = 0;
  planet **p_ptr = p;

  debug (3, "get_planets_score");

  do {
    sprintf (query, score_fmt, (*p_ptr)->planet_id);
    res = do_query (mysql, query);
    row = mysql_fetch_row (res);
    ret += atol (row[0]);

    check_error (mysql);
    mysql_free_result(res);

  } while (*(++p_ptr));

  return ret;
}

void calc_one_battle (MYSQL *mysql, unsigned int planet_id, int game_mode) 
{
  fleet *def, *att;
  planet **def_p, **att_p;
  planet *target_p;
  planet **p_ptr;

  resource del_networth={0,0,0,0};
  unsigned long long att_p_score = 0;

  int i, j;
  
  debug (2, "calc_one_battle");

  att = make_fleet (mysql, 1, planet_id, &att_p, NULL);
  if (!att) {
    fprintf (logfile, "No attacking force\n");
      fflush(logfile);
    return;
  }

  def = make_fleet (mysql, 0, planet_id, &def_p, &target_p);
  if (!def) {
    fprintf (logfile, "ERROR: No defending planet!\n");
      fflush(logfile);
    return;
  }

  att_p_score = sum_planet_score (mysql, att_p);

  fprintf (logfile, 
      "Bashing: [pid:%u] ratio: %f (%f) def: %llu att: %llu\n",
      planet_id,
      (float)target_p->planet_score/ (float) att_p_score,
      (float)att_p_score / (float)target_p->planet_score,
      target_p->planet_score, att_p_score);

  fprintf (logfile,
      "Bashing-f: [pid:%u] ratio: %f (%f) deffleet: %llu attfleet: %llu\n",
      planet_id,
      (float) def->res->total / (float) att->res->total,
      (float) att->res->total / (float) def->res->total, 
      def->res->total, att->res->total);
      fflush(logfile);

  target_p->planet_score += def->res->total;

  debug (3, "calc_one_battle - do_per_init");

  for (i=0; i<=MAX_INIT; i++) {

#ifdef DEBUG_BATTLE
    fprintf (logfile, "init: %d\n", i);
      fflush(logfile);
#endif

    for (j=0; j<att->num_units; j++)
      if (att->units[j].init == i)
	act_initiative (att->units+j, def, target_p, att->res->total, att_p_score);

    for (j=0; j<def->num_units; j++)
      if (def->units[j].init == i)
	act_initiative (def->units+j, att, NULL, 0, 0);

    debug (8, "clean_up_attack(att)");
    clean_up_attack(att);
    debug (8, "clean_up_attack(def)");
    clean_up_attack(def);
  }

  debug (3, "calc_one_battle - dividing damage");

  /* wieviel verliert wer ? */

  /*  divide_roids (mysql, att, att_p, target_p);
   */
  /* divide_damage (att, att_p, NULL); */
  divide_damage (att, att_p, &del_networth);
  divide_damage (def, def_p, &del_networth);
  divide_roids (mysql, att, att_p, target_p);

  update_db (mysql, att_p);
  update_db (mysql, def_p);
  update_db_pds (mysql, target_p);

  salvage_db (mysql, def_p, def->res, &del_networth, game_mode);

  battle_news (mysql, att, def, att_p, def_p, target_p);

  clear_empty_fleets (mysql, att_p, target_p, 1);
  clear_empty_fleets (mysql, def_p, target_p, 0);

  /* free planets / fleets */
#ifdef CLEANUP
  free_fleet (att, 0);
  free_fleet (def, 0);

  p_ptr = att_p;
  do {
    free_planet (*p_ptr);
    *p_ptr = NULL;
  } while (*(++p_ptr));

  p_ptr = def_p;
  do {
    free_planet (*p_ptr);
    *p_ptr = NULL;
  } while (*(++p_ptr));

  target_p = NULL;

  free (att_p);
  free (def_p);

  att_p = NULL;
  def_p = NULL;
#endif /* CLEANUP */
}

void calc_battles (MYSQL *mysql, int game_mode)
{
  MYSQL_RES *res;
  MYSQL_ROW row;
  unit_class *luc;

  debug (1, "calc_battles");

  do_query (mysql,
	    "UPDATE fleet SET type=type-1 WHERE ticks=0 AND full_eta!=0");

  uc = get_unit_class (mysql);
  debug (3, "calc_battles - got units");

#ifdef DEBUG_EXTREME
  fprintf (logfile, "BUG: [%s] (%d)\n",uc_rid[5]->name, strlen(uc_rid[5]->name));
      fflush(logfile);
#endif

  res = do_query (mysql, "SELECT target_id FROM fleet " \
		  "WHERE full_eta!=0 and ticks=0 GROUP BY target_id");

  if (res && mysql_num_rows(res)) {
    
    while ((row = mysql_fetch_row (res))) {
      unsigned int planet_id = atoi (row[0]);
      
#ifdef DEBUG_EXTREME
  fprintf (logfile, "BUG: [%s] (%d)\n",
	   uc_rid[5]->name, strlen(uc_rid[5]->name));
      fflush(logfile);
#endif

      calc_one_battle (mysql, planet_id, game_mode);
      debug (3, "calc_battles - one battle done");
    }
  }  

#ifdef CLEANUP
  luc = uc;
  while (luc->name) {
    free (luc->name);
    luc++;
  }
  free (uc); 
#endif /* CLEANUP */

  check_error (mysql);
  mysql_free_result(res);
}


