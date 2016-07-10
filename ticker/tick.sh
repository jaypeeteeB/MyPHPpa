#!/bin/bash
#
# MyPHPpa ticker
# Copyright (C) 2003, 2007 Jens Beyer
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#

trap clean_up EXIT SIGTERM SIGKILL SIGABRT

function clean_up() {

  echo "Quiting through clean_up"
  rm /tmp/tick*

  trap EXIT
  exit 0
}

if [ ! -f mypa.cfg ]; then
  echo "Config file mypa.cfg missing"
  echo "Fix first this or core_sql.c"
  exit 1
fi 

if [ $# -eq 1 ]; then
  tick=$1
else
  tick=30
fi

if [ ! -f /tmp/ticker.end ]; then
  touch /tmp/ticker.end
fi
touch /tmp/ticker.run.timer

while [ 1 ]; do
  echo "*** "`date`" ***"
  ./ticker mypa.cfg
  echo "*sleeping $sleep"
  ./tick_sleep $tick /tmp/ticker.run.timer
done
