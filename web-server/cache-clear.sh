#!  /bin/bash
#set -x 
source .env
as_core_filename="public/images/as-core.png"
as_core_small_filename="public/images/as-core-small.png"
echo "creating $as_core_filename"
python3 as-core-viz/as-core-graph.py -u $RESTFUL_DATABASE_URL -o $as_core_filename
echo "creating $as_core_small_filename"
python3 as-core-viz/as-core-graph.py -s 200 -u $RESTFUL_DATABASE_URL -o $as_core_small_filename
php bin/console cache:clear --env=prod --no-debug --no-warmup
php bin/console cache:warmup --env=prod
