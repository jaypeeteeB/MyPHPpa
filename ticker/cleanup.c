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
#include <string.h>
#include <time.h>
#include <sys/time.h>

#include "ticker.h"
#include "logging.h"

void delete_user (MYSQL *mysql, unsigned int id)
{
  MYSQL_RES *res;
  MYSQL_ROW row;

  unsigned int x, y, z, allid;
  char buf[256];
  char leader[25], pname[25];

  if (id < 3) {
    /* dont delete admin or moderator */
    return ;
  }

  res = vx_query (mysql, 
                  "SELECT x,y,z,leader,planetname,alliance_id FROM planet WHERE id=%d",
                  id);
  if (res && mysql_num_rows(res)) {
    row = mysql_fetch_row (res);
    x = atoi(row[0]);
    y = atoi(row[1]);
    z = atoi(row[2]);
    strncpy (leader, row[3],25);
    strncpy (pname, row[4],25);
    allid =  atoi(row[5]);
  } else {
    return;
  }

  fprintf (logfile, "DELETE: id=%d, %s of %s [%d,%d,%d]\n", id, 
	leader, pname, x,y,z);
  
  vx_query (mysql, "INSERT INTO news set planet_id=1,date=now()," \
                   "type=10,text='Deleted %s of %s (%d:%d:%d) [%d]'," \
                   "tick=%d",
                   leader, pname, x, y, z, id, mytick);

  vx_query (mysql, "UPDATE user SET password='delete' WHERE planet_id=%d", id);
  vx_query (mysql, "DELETE FROM rc_build WHERE planet_id=%d", id);
  vx_query (mysql, "DELETE FROM rc WHERE planet_id=%d", id);
  vx_query (mysql, "DELETE FROM scan_build WHERE planet_id=%d", id);
  vx_query (mysql, "DELETE FROM scan WHERE planet_id=%d", id);
  vx_query (mysql, "DELETE FROM journal WHERE planet_id=%d", id);
  vx_query (mysql, "DELETE FROM journal WHERE target_id=%d", id);
  vx_query (mysql, "DELETE FROM pds_build WHERE planet_id=%d", id);
  vx_query (mysql, "DELETE FROM pds WHERE planet_id=%d", id);
  vx_query (mysql, "DELETE FROM unit_build WHERE planet_id=%d", id);

/*
  vx_query (mysql, "UPDATE mail set sender_id=0 WHERE sender_id=%d", id);
  vx_query (mysql, "UPDATE mail set planet_id=0 WHERE planet_id=%d", id);
  vx_query (mysql, "UPDATE msg set planet_id=0,folder=0 WHERE planet_id=%d", id);
  vx_query (mysql, "DELETE FROM mail WHERE sender_id=%d OR planet_id=%d", id, id);
*/

  vx_query (mysql, "DELETE FROM msg WHERE planet_id=%d", id);
  vx_query (mysql, "DELETE FROM news WHERE planet_id=%d", id);

  res = vx_query (mysql, "SELECT fleet_id FROM fleet WHERE planet_id=%d", id);
  if (res && mysql_num_rows(res)) {
    while ((row = mysql_fetch_row (res))) {
      vx_query (mysql, "DELETE FROM units WHERE id=%s", row[0]);
    }
    mysql_free_result(res);
  }

  vx_query (mysql, "DELETE FROM fleet WHERE planet_id=%d", id);
  vx_query (mysql, "DELETE FROM user WHERE planet_id=%d", id);
  vx_query (mysql, "DELETE FROM planet WHERE id=%d", id);

  res = vx_query (mysql, "SELECT exile_id FROM galaxy " \
                  "WHERE x=%d AND y=%d AND  exile_id=%d", x, y, id);
  if (res && mysql_num_rows(res)) {
    mysql_free_result(res);

    vx_query (mysql, "UPDATE planet SET exile_vote=0 " \
              "WHERE x=%d AND y=%d", x, y);
    vx_query (mysql, "UPDATE galaxy SET exile_id=0, exile_date=0 "\
              "WHERE x=%d AND y=%d", x, y);
  }

  if(allid!=0) {
    vx_query (mysql, "UPDATE alliance SET members=members-1 "\
                     "WHERE id=%d", allid);
    res = vx_query (mysql, "SELECT hc FROM alliance WHERE id=%d AND hc=%d",allid,id);
    if (res && mysql_num_rows(res) == 1) {
      /* need to delete alliance */
      vx_query (mysql, "DELETE FROM alliance WHERE id=%d",allid);
      vx_query (mysql, "UPDATE planet SET alliance_id=0 WHERE alliance_id=%d",allid);

      sprintf (buf, "ALLIANCE deleted: %d",allid);
      sendmessage (mysql, 1, buf);
    }
  }

  vx_query (mysql, "UPDATE galaxy set members=members-1 where x=%d AND y=%d",x,y);
  vx_query (mysql, "UPDATE planet set vote=0 WHERE vote=%d "\
                   "AND x=%d AND y=%d", id, x, y);
}

void sendmessage (MYSQL *mysql, int pid, const char *msg)
{
  vx_query (mysql, "INSERT INTO news SET planet_id=%d,"\
    "date=now(),tick=%d, type=10, text ='%s'", pid, mytick, msg);

  vx_query (mysql, "UPDATE planet SET has_news=1 WHERE planet_id=%d", pid);
}

void exilemessage (MYSQL *mysql, int gid, int x, int y, int pid, int type)
{
  static char msg[512];
  MYSQL_RES *res;
  MYSQL_ROW row;

  debug(4,"exilemessage");

  res = vx_query (mysql, "SELECT leader,planetname,x,y,z FROM planet WHERE id=%d", pid);
  row = mysql_fetch_row (res);

  switch (type) {
  case 1:
    sprintf (msg, "%s of %s (%s:%s:%s) has been exiled.",
             row[0], row[1], row[2], row[3], row[4]);
    break;
  case 0:
    sprintf (msg, "The exile of %s of %s (%s:%s:%s) has failed.",
             row[0], row[1], row[2], row[3], row[4]); 
    break;
  default:
    sprintf (msg, "The exile of %s of %s (%s:%s:%s) has been cancelled.",
             row[0], row[1], row[2], row[3], row[4]); 
  }
  check_error (mysql);
  mysql_free_result(res);

  fprintf (logfile, "[Exile]: %s\n", msg);
  sendmessage (mysql, 1, msg);

  res = vx_query (mysql, "SELECT id FROM planet WHERE x=%d AND y=%d", x, y);

  if (!res) return;
  while ((row = mysql_fetch_row (res))) {
    sendmessage (mysql, atoi(row[0]), msg);
  }
  check_error (mysql);
  mysql_free_result(res);
}

#define GALAXY_SIZE 7

int get_new_coords (MYSQL *mysql, int *x, int *y, int *z, int *gid)
{
  MYSQL_RES *res;
  MYSQL_ROW row;

  int low=1, high=1, max_high=0;
  int cnt=0, rval;

  debug (4, "get_new_coords");

  /* universe size */
  res = do_query (mysql, "SELECT x FROM galaxy ORDER BY x DESC LIMIT 1");
  row = mysql_fetch_row (res);
  max_high = atoi(row[0]);

  check_error (mysql);
  mysql_free_result(res);

  /* lowest cluster w free planets */
  res = vx_query (mysql, "SELECT x FROM galaxy WHERE members<%d AND !(x=1 AND y=1) "\
                         "ORDER BY x ASC LIMIT 1", GALAXY_SIZE);
  if (!res || mysql_num_rows(res)<1) {
    return 1;
  }
  row = mysql_fetch_row (res);
  low = atoi(row[0]);

  check_error (mysql);
  mysql_free_result(res);

  /* highest cluster w members */
  res = vx_query (mysql, "SELECT x FROM galaxy WHERE members>0 "\
                         "ORDER BY x DESC LIMIT 1"); 
  row = mysql_fetch_row (res);
  high = atoi(row[0]);

  check_error (mysql);
  mysql_free_result(res);

  if (high < low)
    high = low;

  /* check Open clusters */
  do {
    res = vx_query (mysql, "SELECT x,y,id FROM galaxy WHERE x>=%d AND x<=%d "\
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
    srand ((time(NULL) * (*gid + 1)));
    rval = 1 + (int) (((float)cnt - 1.) * rand()/(RAND_MAX+1.0));
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

int exile_planet (MYSQL *mysql, int galid, int pid)
{
  int x=0, y=0, z=0, gid=0;

  debug(4, "exile_planet");
  /* new_coords */
  if ( get_new_coords (mysql, &x, &y, &z, &gid) ) {
    return 1;
  } else if (gid == galid) {
    /* retry once */
    if ( get_new_coords (mysql, &x, &y, &z, &gid) )
      return 1;
  }

  fprintf (logfile, "[Exile]: New coords: %d (%d:%d:%d) [%d->%d]\n", 
           pid, x, y, z, galid, gid);

  /* move planet */
  vx_query (mysql, "UPDATE galaxy set members=members+1 WHERE id=%d",gid);
  vx_query (mysql, "UPDATE planet SET x=%d,y=%d,z=%d, "\
     "vote=0,exile_vote=0,has_politics=0 WHERE id=%d",
     x, y, z, pid);
  /* missing check if valid move */
  vx_query (mysql, "UPDATE galaxy set members=members-1 WHERE id=%d",galid);

  /* missing remove politics */
  return 0;
}

void remove_vote (MYSQL *mysql, int gid, int x, int y, int pid, int found)
{
  static char upd_p_fmt[] = "UPDATE planet SET exile_vote=0 " \
      "WHERE x=%d AND y=%d";
  static char upd_g_fmt[] = "UPDATE galaxy SET exile_id=0, exile_date=0 "\
      "WHERE id=%d";

  debug(4, "remove_vote");
  vx_query (mysql, upd_p_fmt, x, y);
  vx_query (mysql, upd_g_fmt, gid);

  exilemessage (mysql, gid, x, y, pid, found);
}

/*
  res = do_query (mysql,"SELECT id, x, y, exile_id "\
        "FROM galaxy WHERE exile_id>0 AND exile_date<now()");
*/
void check_exile (MYSQL *mysql, int gid, int x, int y, int pid)
{
  static char c_fmt[] = "SELECT has_hostile, gal_hostile FROM planet WHERE id=%d";
  static char q_fmt[] = "SELECT exile_vote FROM planet, user " \
      "WHERE planet.x=%d AND planet.y=%d AND planet.id = user.planet_id " \
      "AND user.last > now() - interval 36 HOUR AND mode not in (0, 4)";

  MYSQL_RES *res;
  MYSQL_ROW row;

  int found=0, exile_cnt=0, active_cnt=0;

  debug (3, "check_exile");

  /* target still there ? */
  res = vx_query (mysql, c_fmt, pid);
  if (!res || mysql_num_rows(res)==0) {
    fprintf (logfile, "[Exile]: cancelled - planet gone [%d]\n", pid);
    fflush(logfile);
    remove_vote (mysql, gid, x, y, pid, -1);
    return;
  }

  row = mysql_fetch_row(res);
  if (atoi(row[0])!=0 || atoi(row[1])!=0) {
    /* under attack - wait a tick */
    fprintf (logfile, "[Exile]: planet [%d] - gal under attack; delayed\n", pid);
    mysql_free_result(res);
    return;
  }

  check_error (mysql);
  mysql_free_result(res);

  /* vote succesfull ? */
  res = vx_query (mysql, q_fmt, x, y);
  if (!res || mysql_num_rows(res)<1)
    return;

  while ((row = mysql_fetch_row (res))) {
    active_cnt++;
    if (atoi(row[0]) == 1) {
      exile_cnt++;
    }
  }
  check_error (mysql);
  mysql_free_result(res);

  fprintf (logfile, "[Exile]: (%d:%d) [%d], %d / %d\n", 
           x, y, pid, exile_cnt, active_cnt);

  /* exile or not */
  if ((exile_cnt*1.5) > active_cnt) {
    if (exile_planet (mysql, gid, pid)) {
      return;
    }
    found = 1;
  }

  /* remove_vote */
  remove_vote (mysql, gid, x, y, pid, found);
  check_error (mysql);
}

void do_clean_ups (MYSQL *mysql)
{
  MYSQL_RES *res;
  MYSQL_ROW row;

  debug (1, "do_clean_ups");

  /* auto logout */
  /* alt where planet.mode & 0x0F = 2 */

  res = do_query (mysql,"SELECT user.planet_id FROM user, planet " \
		  "WHERE planet.id=user.planet_id " \
		  "AND now() - INTERVAL 30 MINUTE > user.last "\
		  "AND (planet.mode = 0xF2 OR planet.mode = 2)");

  if (res && mysql_num_rows(res)) {

    while ((row = mysql_fetch_row (res))) {
      vx_query(mysql, "UPDATE user SET uptime=" \
             "SEC_TO_TIME(UNIX_TIMESTAMP(last) - UNIX_TIMESTAMP(login_date) + TIME_TO_SEC(uptime)) " \
             "WHERE planet_id='%s'", row[0]);

      vx_query (mysql, 
           "UPDATE planet SET mode=((mode & 0xF0) + 1) WHERE id=%s", row[0]);
      do_log_id (mysql, atoi(row[0]), C_LOGOUT, T_AUTO, "");
    }

  }
  check_error (mysql);
  mysql_free_result(res);

  /* protection */
  /* alt where planet.mode & 0xF0 */
  res = do_query (mysql,"SELECT user.planet_id FROM user,planet,general " \
		  "WHERE planet.id=user.planet_id " \
                  "AND planet.mode>127 " \
		  "AND general.tick>=user.first_tick+71");

  if (res && mysql_num_rows(res)) {
    while ((row = mysql_fetch_row (res))) {
      vx_query (mysql, "UPDATE planet SET mode=(mode & 0xF)  WHERE id=%s", row[0]);
    }

  }
  check_error (mysql);
  mysql_free_result(res);
  
  /* sleeping */
  /* alt where planet.mode & 0x0F = 3 */
  res = do_query (mysql,"SELECT user.planet_id FROM user,planet " \
		  "WHERE planet.id=user.planet_id " \
                  "AND (planet.mode = 0xF3 OR planet.mode = 3) " \
		  "AND user.last_sleep < now() - INTERVAL 6 HOUR");

  if (res && mysql_num_rows(res)) {
    while ((row = mysql_fetch_row (res))) {
      vx_query (mysql, "UPDATE planet SET mode=(mode & 0xF0)+1  WHERE id=%s", row[0]);
      do_log_id (mysql, atoi(row[0]), C_FLOW, T_SLEEP, "");
    }

  }
  check_error (mysql);
  mysql_free_result(res);
  
  /* clean politics */
  res = do_query (mysql,"SELECT id FROM politics "  \
                  "WHERE now() - INTERVAL 7 DAY > date");
  if (res && mysql_num_rows(res)) {
    while ((row = mysql_fetch_row (res))) {
      vx_query (mysql, "DELETE FROM politics WHERE id = %s", row[0]);
      vx_query (mysql, "DELETE FROM poltext WHERE thread_id = %s", row[0]);
    }
  }

  check_error (mysql);
  mysql_free_result(res);

  /* delete idle/old/test accounts */
  /* only if mytick > 24 hours = 120*24 = 1440 */
  if (mytick > 2880) {
    res = do_query (mysql, "SELECT planet.id FROM planet,user "\
                  "WHERE planet.id=user.planet_id "\
                  "AND planet.id>2 "\
                  "AND (metalroids+crystalroids+eoniumroids+uniniroids) < 4 " \
                  "AND (user.last < NOW() - INTERVAL 24 HOUR " \
		  "OR (user.last IS NULL "\
		  "AND user.signup < NOW() - INTERVAL 24 HOUR))");
    if (res && mysql_num_rows(res)) {
      while ((row = mysql_fetch_row (res)))
        delete_user(mysql, atoi(row[0]));
    }
  }

  /* delete deleted accounts */
  res = do_query (mysql, "SELECT planet_id FROM user "\
                  "WHERE delete_date !=0 AND delete_date > last " \
                  "AND delete_date < NOW() - INTERVAL 12 HOUR");
  if (res && mysql_num_rows(res)) {
    while ((row = mysql_fetch_row (res)))
      delete_user(mysql, atoi(row[0]));
  }

  check_error (mysql);
  mysql_free_result(res);

  /* delete banned accounts */

  res = do_query (mysql, "SELECT planet.id FROM planet,user "\
                  "WHERE planet.id=user.planet_id AND mode=0 AND " \
                  "user.last < NOW() - INTERVAL 36 HOUR");
  if (res && mysql_num_rows(res)) {
    while ((row = mysql_fetch_row (res)))
      delete_user(mysql, atoi(row[0]));
  }

  check_error (mysql);
  mysql_free_result(res);

  /* check for exiling */
  res = do_query (mysql,"SELECT id, x, y, exile_id "\
                 "FROM galaxy WHERE exile_id>0 AND exile_date<now()");
  if (res && mysql_num_rows(res)) {
    while ((row = mysql_fetch_row (res)))
      check_exile(mysql, atoi(row[0]), atoi(row[1]),atoi(row[2]),atoi(row[3]));
  }

  check_error (mysql);
  mysql_free_result(res);

  /* clean news */
  do_query (mysql, "DELETE FROM news WHERE date < now() - INTERVAL 48 HOUR");

  /* clean journal (1) */
  do_query (mysql, 
    "DELETE FROM journal WHERE hidden=1 AND date < NOW() - INTERVAL 10 MINUTE");

  /* clean journal (2) */
  do_query (mysql, 
    "DELETE FROM journal WHERE hidden=0 AND date < NOW() - INTERVAL 48 HOUR");

  /* fleet cleanup */
  do_query (mysql, "DELETE FROM units WHERE num=0");

  /* clear hostile */
}

