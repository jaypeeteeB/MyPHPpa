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
#include <time.h>
#include <sys/time.h>
#include <signal.h>
#include <assert.h>
#include <string.h>

#include <mysql/mysql.h>
#include "ticker.h"

FILE *logfile;

#define GALAXY_SIZE 7

int get_new_coords (MYSQL *mysql, int *x, int *y, int *z, int *gid)
{
  MYSQL_RES *res;
  MYSQL_ROW row;

  int low=1, high=1, max_high=0;
  int cnt=0, rval;

  /* universe size */
  res = do_query (mysql, "SELECT x FROM galaxy ORDER BY x DESC LIMIT 1");
  row = mysql_fetch_row (res);
  max_high = atoi(row[0]);

  check_error (mysql);
  mysql_free_result(res);

  /* lowest cluster w free planets */
  res = vx_query (mysql, 
    "SELECT x FROM galaxy WHERE members<%d AND !(x=1 AND y=1) "\
    "ORDER BY x ASC LIMIT 1", GALAXY_SIZE);
  if (!res || mysql_num_rows(res)<1) {
    return 1;
  }
  row = mysql_fetch_row (res);
  low = atoi(row[0]);

  check_error (mysql);
  mysql_free_result(res);

  /* highest cluster w members */
  res = vx_query (mysql,
    "SELECT x FROM galaxy WHERE members>0 "\
    "ORDER BY x DESC LIMIT 1"); 
  row = mysql_fetch_row (res);
  high = atoi(row[0]);

  check_error (mysql);
  mysql_free_result(res);

  if (high < low)
    high = low;

  /* check Open clusters */
  do {
    res = vx_query (mysql, 
      "SELECT x,y,id FROM galaxy WHERE x>=%d AND x<=%d "\
      "AND members<%d AND !(x=1 and y=1) GROUP BY x, y",
      low, high, GALAXY_SIZE);
    if (!res) return 1;

    cnt = mysql_num_rows(res); 

    if (cnt==0) {
      /* next cluster */
      low = high + 1;
      if (low > max_high) return 1;
      high = low;
    }
  } while (cnt==0);

  if (cnt != 1) {
    /* generate random value */
    srand ((time(NULL)* (*gid + 1)));
    rval = 1 + (int) (((float)cnt-1.)*rand()/(RAND_MAX+1.0));
  } else {
    rval = 1;
  }

  /* fetch bis zum nten */
  while (rval>1) {
    row = mysql_fetch_row(res);
    rval--;
  }
  row = mysql_fetch_row(res);
  *x = atoi(row[0]);
  *y = atoi(row[1]);
  *gid = atoi(row[2]);

  mysql_free_result(res);

  res = vx_query (mysql, "SELECT z FROM planet WHERE x=%d AND y=%d "\
    "ORDER BY z DESC LIMIT 1", *x, *y);
  if (res && mysql_num_rows(res)) {
    row = mysql_fetch_row(res);
    *z = 1 + atoi(row[0]);
  } else 
    *z = 1;

  check_error (mysql);
  mysql_free_result(res);
  
  return 0;
}

int main(int argc, char *argv[])
{
  MYSQL *mysql;
  MYSQL_RES *res;
  MYSQL_ROW row;
  int x=0, y=0, z=0, g=0, r;

  logfile = stdout;

  mysql = init_connection(NULL);

  r = get_new_coords (mysql, &x, &y, &z, &g);
  printf("%d (%d,%d:%d) [%d]\n",r,x,y,z,g);
  g = 0;
  r = get_new_coords (mysql, &x, &y, &z, &g);
  printf("%d (%d,%d:%d) [%d]\n",r,x,y,z,g);

  r = get_new_coords (mysql, &x, &y, &z, &g);
  printf("%d (%d,%d:%d) [%d]\n",r,x,y,z,g);

  r = get_new_coords (mysql, &x, &y, &z, &g);
  printf("%d (%d,%d:%d) [%d]\n",r,x,y,z,g);

  close_connection(mysql);
  return (0);
}
