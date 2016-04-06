# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|
  config.vm.box = "ubuntu/wily64"

  config.vm.network "forwarded_port", guest: 8000, host: 8000, auto_correct:true

  config.vm.provider "virtualbox" do |v|
    v.memory = 2048
    v.cpus = 2
  end

  config.vm.provision "shell", inline: <<-SHELL
    debconf-set-selections <<< "postfix postfix/mailname string dev.gendloc.gend"
    debconf-set-selections <<< "postfix postfix/main_mailer_type string 'Internet Site'"
    sudo apt-get update
    sudo apt-get install -y php5 php5-curl php5-pgsql postgresql postgresql-client postgresql-contrib postgis postfix
    sudo -u postgres psql -c "create user admin with password 'password';"
    sudo -u postgres psql -c "alter user admin with superuser;"
    sudo curl -LsS https://symfony.com/installer -o /usr/local/bin/symfony
    sudo chmod a+x /usr/local/bin/symfony
    sudo curl -sS https://getcomposer.org/installer | php
    cd /vagrant
    php bin/console doctrine:database:create
    php bin/console doctrine:schema:update -f
    sudo -u postgres psql -c "create extension pgcrypto" db_gendloc
    sudo -u postgres psql -c "create extension postgis" db_gendloc
    sudo -u postgres psql -c "insert into app_unites (id, name, tel, coordinates) values (1, 'SECOURSMONT', '00', ST_GeomFromText('point(2.271 48.815)'));" db_gendloc
    sudo -u postgres psql -c "insert into app_users (id, username, password, email, is_active, unite, role) values (1, 'admin', crypt('admin', 'bcrypt'), 'admin@gend.loc', true, 1, 'ROLE_ADMIN');" db_gendloc
    sudo -u postgres psql -c "insert into app_users (id, username, password, email, is_active, unite, role) values (2, 'ope', crypt('ope', 'bcrypt'), 'ope@gend.loc', true, 1, 'ROLE_OPERATOR');" db_gendloc
    sudo -u postgres psql -c "ALTER TABLE app_geoloc ADD COLUMN coordinates geometry(Point,4326);" db_gendloc
    sudo -u postgres psql -c "ALTER TABLE app_geoloc ALTER COLUMN id SET DEFAULT nextval('"app_geoloc_id_seq"');" db_gendloc
  SHELL
end
