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

#ifndef __battle_h__
#define __battle_h__

#define MAX_FLEETS 5
#define MAX_INIT 15

#define CLASS_CLASSIC 1
#define CLASS_EMP 2
#define CLASS_CLOAKED 3
#define CLASS_CAP 4
#define CLASS_PDS 5
#define CLASS_MISSILE 6

#define CAP_UNIT 13

typedef struct unit_class_struct {
  char *name;
  unsigned int id;
  unsigned int type;
  unsigned int class;
  unsigned int init;
  unsigned int agility;
  unsigned int weapon_speed;
  unsigned int guns;
  unsigned int power;
  unsigned int armor;
  unsigned int resistance;
  unsigned int t1, t2, t3;
  unsigned int metal;
  unsigned int crystal;
  unsigned int eonium;
} unit_class;

typedef struct unit_struct {
  unsigned int id;
  unsigned int init;
  unsigned int num;
  unsigned int stunned, to_be_stunned;
  unsigned int killed, to_be_killed;
  unsigned int capping, hidden;
  unsigned int targeted, hits;
  unit_class *u;
} unit;

typedef struct resource_struct {
  unsigned long long metal;
  unsigned long long crystal;
  unsigned long long eonium;
  unsigned long long total;
} resource;

typedef struct fleet_struct {
  unsigned int num_units;
  unsigned int fleet_id;
  unit **rid;
  unit *units;
  resource * res;
} fleet;

typedef struct planet_struct {
  fleet f[MAX_FLEETS];
  fleet *pds;

  unsigned int planet_id;

  unsigned long long planet_score;
  unsigned int roids[4];
  unsigned int cap_roids[5];
  
  resource *lost;

  fleet *total_fleet;
} planet;

#endif /* __battle_h__ */
