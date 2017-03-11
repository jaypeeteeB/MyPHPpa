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
  if( (fd = fopen(tname,"a"))) {
    fprintf(fd, "%s\n", ctime(&now));
    fclose (fd);
  } else 
    fprintf (logfile, "Failed to open ticker_file %s\n", tname);
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
  char *tickstartf = "/tmp/ticker.run";
  char *tickendf   = "/tmp/ticker.end";
  options *opt = NULL;
  int game_mode = 0;

  set_alarm (125);
  gettimeofday (&start, NULL);


  now = time(NULL);

  if (argc > 1) {
    opt = read_cfg (argc, argv);
    if (opt->logfile) tickerlog = strdup (opt->logfile);
    if (opt->tickstart) tickstartf = strdup (opt->tickstart);
    if (opt->tickend)   tickendf   = strdup (opt->tickend);
    game_mode = opt->resource;
  }

  if (!(logfile = fopen(tickerlog,"a"))) {
    fprintf (stderr, "Could not open logfile: %s\n", tickerlog);
    logfile = stderr;
  }

  fprintf(logfile, "\nTicker start: %s", ctime(&now));

  mysql = init_connection(opt);

  mytick = get_tick(mysql);
  write_ticker_file(tickstartf);

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
  write_ticker_file(tickendf);

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

