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

#include "ticker.h"

void print_runtime (struct timeval *);
unsigned int mytick;
FILE *logfile;

/***********************************/

unsigned int get_tick(MYSQL *mysql)
{
  MYSQL_RES *res;
  MYSQL_ROW row;
  unsigned int retval = 0;

  res = do_query (mysql,"SELECT tick FROM general");

  if (res && mysql_num_rows(res) == 1) {
    row = mysql_fetch_row (res);
    retval = atoi (row[0]);
    mysql_free_result(res);
  }
  check_error (mysql);
  return retval;
}

void write_ticker_file(char *tname) 
{
  FILE *fd;
  time_t now = time(NULL);
  fd = fopen(tname,"a");
  fprintf(fd, "%s\n", ctime(&now));
  fclose (fd);
}

/***********************************/

void quit_alarm (int sign)
{
  fprintf (logfile, "Caught SIGALRM - forced quiting\n");
  exit (1);
}

void set_alarm (int secs) 
{
  struct itimerval value;
  
  value.it_interval.tv_sec =  0L;
  value.it_interval.tv_usec = 0L;
  value.it_value.tv_sec  = secs;
  value.it_value.tv_usec  = 0L;

  signal (SIGALRM, quit_alarm);
  setitimer (ITIMER_REAL, &value, NULL);
}

/***********************************/

options *read_cfg (int argc, char *argv[]) 
{
  FILE *cfg;
  char line[82];
  char cmd[82], opt[82];
  options *ret;

  if (argc < 2) {
    fprintf (logfile, "Missing argument for DB-parameters\n");
    fprintf (stderr, "Usage: %s padb.cfg\n",argv[0]);
    exit (1);
  }

  if (!(cfg = fopen(argv[1],"r"))) {
    fprintf (logfile, "Could not open %s for reading\n", argv[1]);
    fprintf (stderr, "Usage: %s %s\n", argv[0], argv[1]);
    exit (1);
  }

  ret = calloc (sizeof(options), 1);
  assert (ret);

  while ( fgets (line, 80, cfg) ) {
    char * eq;

    if (! (eq = strchr (line, '=') ) )
      continue;
    *eq = ' ';

    if ( 2 != sscanf (line, "%s %s", cmd, opt) )
      continue;

    /*
     * fprintf (stderr, "Option: [%s]=[%s]\n", cmd, opt);
     */

    if (!strncmp (cmd, "db_host", 7)) {
      ret->db_host = strdup (opt);
    } else if (!strncmp (cmd, "db_db", 5)) {
      ret->db_db = strdup (opt);
    } else if (!strncmp (cmd, "db_user", 7)) {
      ret->db_user = strdup (opt);
    } else if (!strncmp (cmd, "db_passwd", 9)) {
      ret->db_passwd = strdup (opt);
    } else if (!strncmp (cmd, "db_socket", 9)) {
      ret->db_socket = strdup (opt);
    } else if (!strncmp (cmd, "db_port", 7)) {
      ret->db_port = atoi(opt);
    } else if (!strncmp (cmd, "logfile", 7)) {
      ret->logfile = strdup (opt);
    } else if (!strncmp (cmd, "sql_log", 7)) {
      ret->sql_log = strdup (opt);
    } else if (!strncmp (cmd, "resource", 7)) {
      if (!strncmp (opt, "havoc", 5))
	ret->resource = 1;
      else
	ret->resource = 0;
    }

  } 
  fclose (cfg);
  return ret;
}

/***********************************/

void print_runtime (struct timeval *start)
{
  struct timeval end;

  gettimeofday (&end, NULL);
  if ( end.tv_usec - start->tv_usec < 0 ) {
    end.tv_usec += 1000000L;
    end.tv_sec -= 1;
  }
  fprintf (logfile, "   ****: %ld s %03ld ms\n",
          end.tv_sec - start->tv_sec,
          (end.tv_usec - start->tv_usec)/1000);
  fflush (logfile);
}

int main(int argc, char *argv[])
{
  MYSQL *mysql;
  struct timeval start, end;
  time_t now;
  char *tickerlog = "ticker.log";
  options *opt = NULL;
  int game_mode = 0;

  set_alarm (125);
  gettimeofday (&start, NULL);


  now = time(NULL);

  if (argc > 1) {
    opt = read_cfg (argc, argv);
    if (opt->logfile) tickerlog = strdup (opt->logfile);
    game_mode = opt->resource;
  }

  if (!(logfile = fopen(tickerlog,"a"))) {
    fprintf (stderr, "Could not open logfile: ticker.log\n");
    logfile = stderr;
  }

  fprintf(logfile, "\nTicker start: %s", ctime(&now));

  mysql = init_connection(opt);

  mytick = get_tick(mysql);
  write_ticker_file("/tmp/ticker.run");

  update_build (mysql); 
  move_fleets (mysql); 
  print_runtime (&start);

  do_query (mysql, "UPDATE general set tick=tick+1");
  mytick++;

  calc_battles (mysql, game_mode); 
  print_runtime (&start);

  update_research (mysql); 
  print_runtime (&start);
  update_resources (mysql, game_mode); 
  print_runtime (&start);

  fflush (logfile);
  /* 
  if ((mytick/4)*4 == mytick) {
  */
    calc_score (mysql); 
    print_runtime (&start);

  /* if ((mytick/10)*10 == mytick) { */
    do_clean_ups (mysql); 
    print_runtime (&start);
  /* } */

  /* update ticks */
  /* do_query (mysql, "UPDATE general set tick=tick+1");
   */
  write_ticker_file("/tmp/ticker.end");

  close_connection(mysql);
  
  gettimeofday (&end, NULL);
  if ( end.tv_usec - start.tv_usec < 0 ) {
    end.tv_usec += 1000000L;
    end.tv_sec -= 1;
  }

  now = time(NULL);
  fprintf(logfile, "Ticker end  : %s", ctime(&now));

  fprintf (logfile, "Runtime: %ld s %03ld ms\n",
          end.tv_sec - start.tv_sec,
          (end.tv_usec - start.tv_usec)/1000); 

  if (logfile != stderr)
    fclose (logfile);

  return 0;
}

