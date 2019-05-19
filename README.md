## installing Docker

* `sudo wget -qO- https://get.docker.com/ | sh`
* `docker-compose` needs pip, on centos `sudo yum install python-pip`
* `sudo pip install docker-compose`
* `sudo service docker restart`

## using this docker
* make sure build/data is writeable for mysql data
* docker-compose up
* might need to create mysql user manually
* to see IPs use `/util/list_ips.sh`  
* open the PHP ip in browser
