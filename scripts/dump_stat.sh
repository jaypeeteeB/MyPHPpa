#!/bin/bash

CFGFILE='db.cfg'
if [ ! -f $CFGFILE ]; then
  echo "Missing cfgfile '$CFGILE' "
  exit 1
fi
source $CFGFILE

if [ -z ${BASEDIR} -o -z ${DBNAME} ]; then
  echo "Missing config variable"
  exit 2
fi

if [ ! -d ${BASEDIR} ]; then
  mkdir -p ${BASEDIR}
fi

rm -f ${BASEDIR}/universe.db
rm -f ${BASEDIR}/galaxy.db
rm -f ${BASEDIR}/tick.db

mysql -u ${DBUSER} -h ${DBHOST} -p${DBPASS} ${DBNAME} <<EOF

SELECT now(), tick FROM general
 INTO OUTFILE '${BASEDIR}/tick.db'
 FIELDS terminated by ';';

SELECT x, y, z, planetname, leader, score, 
 metalroids + crystalroids + eoniumroids + uniniroids as size 
 FROM planet ORDER BY x,y,z ASC 
 INTO OUTFILE '${BASEDIR}/universe.db' 
 FIELDS terminated by ';';

SELECT p.x AS x , p.y AS y, g.name, SUM(p.score) AS score,
 SUM(metalroids + crystalroids +eoniumroids + uniniroids) AS size
 FROM planet AS p, galaxy AS g 
 WHERE g.x=p.x AND g.y = p.y
 GROUP by x, y ORDER BY x, y
 INTO OUTFILE '${BASEDIR}/galaxy.db'
 FIELDS terminated by ';';
EOF

if [ ! -f ${BASEDIR}/tick.db ]; then
  echo "DBaccess failed"
  exit 3
fi

echo '# Date, Tick' > ${BASEDIR}/universe.txt 
echo -n '# ' >> ${BASEDIR}/universe.txt
cat ${BASEDIR}/tick.db >> ${BASEDIR}/universe.txt
echo '# x y z name leader score size' >> ${BASEDIR}/universe.txt
cat ${BASEDIR}/universe.db | sed 's/&amp\\;/\&/g; s/&quot\\;/"/g; s/&lt\\;/</g; s/&gt\\;/>/g; s/\\;//g' >> ${BASEDIR}/universe.txt

echo '# Date, Tick' > ${BASEDIR}/galaxy.txt 
echo -n '# ' >> ${BASEDIR}/galaxy.txt
cat ${BASEDIR}/tick.db >> ${BASEDIR}/galaxy.txt
echo '# x y name score size' >> ${BASEDIR}/galaxy.txt
cat ${BASEDIR}/galaxy.db | sed 's/&amp\\;/\&/g; s/&quot\\;/"/g; s/&lt\\;/</g; s/&gt\\;/>/g; s/\\;//g' >> ${BASEDIR}/galaxy.txt

mv ${BASEDIR}/universe.txt ${BASEDIR}/galaxy.txt ${TARGETDIR}

rm -f ${BASEDIR}/universe.db
rm -f ${BASEDIR}/galaxy.db
rm -f ${BASEDIR}/tick.db

