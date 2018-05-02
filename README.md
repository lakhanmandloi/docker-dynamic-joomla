# experiment-docker-joomla


## Steps -

1. Check Docker-compose & Docker is installed
2. Clone this repo
3. Copy the configs folder sent in email inside this repo in root.
4. add host entries - site1.local and site2.local
5. Go to repo and run command ```docker-compose up```
6. Check site on URL - ```site1.local:8085``` & ```site2.local:8085``` . 


## Points to note - 

1. Site uses same codebase but diff config & DB
2. Uses multiple php-fpm instances for load balancing.  Check with ```docker ps```


## Things Pending - 

1. Images folder nginx url rewrite
2. Bring project codebase inside the image




