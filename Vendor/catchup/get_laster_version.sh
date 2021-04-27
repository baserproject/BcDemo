#!/usr/local/bin/bash

wget -P /home/baser-dev/www/trial.basercms.net/src/ https://basercms.net/packages/download/basercms/latest_version
unzip -d /home/baser-dev/www/trial.basercms.net/src/ /home/baser-dev/www/trial.basercms.net/src/latest_version
version=$(head -n 1 /home/baser-dev/www/trial.basercms.net/src/basercms/lib/Baser/VERSION.txt)
mv /home/baser-dev/www/trial.basercms.net/src/basercms /home/baser-dev/www/trial.basercms.net/src/$version

rm -rf /home/baser-dev/www/trial.basercms.net/src/basercms
rm -rf /home/baser-dev/www/trial.basercms.net/src/latest_version

echo "\nDo you want to create new symlink of new version lib directory? (yes/no)"
read answer

case $answer in
    yes)
		cd /home/baser-dev/www/trial.basercms.net/
		rm -rf /home/baser-dev/www/trial.basercms.net/lib
		ln -s ./src/$version/lib ./lib
		echo -e "\n"
        echo -e "create symlink. \n"
        echo -e "update to basercms => https://trial.basercms.net/update \n"
        ;;
    *)
esac

# reset DB
/home/baser-dev/www/trial.basercms.net/src/reset_db.sh

echo -e "アップデート後にupdate_org_db.shを実行してください \n"
