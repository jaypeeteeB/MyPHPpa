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
#include <sys/stat.h>
#include <unistd.h>

void touch(char *file)
{
  FILE *fd;

  fd = fopen(file,"w");
  fwrite("x",1,1,fd);
  fclose(fd);
}

int main (int argc, char * argv[])
{
  time_t diff_time, target_time;
  struct stat st;
  int ret;

  if (argc != 3) {
    fprintf (stderr, "usage: %s target_sleep_time control_file\n", argv[0]);
    exit (1);
  }

  if ( stat (argv[2], &st) ) {
    fprintf (stderr, "Failed to stat %s\n", argv[2]);
    exit (2);
  }

  target_time = atoi (argv[1]);

  diff_time = time(NULL) - st.st_mtime;
  fprintf (stderr, "stat %s: diff %d sleep %d\n", 
	   argv[0], (int) diff_time, (int) (target_time - diff_time));
  
  if (diff_time >= target_time) {
    touch (argv[2]);
    return (0);
  }
  ret = sleep (target_time - diff_time);
  touch (argv[2]);

  return ret;
}


