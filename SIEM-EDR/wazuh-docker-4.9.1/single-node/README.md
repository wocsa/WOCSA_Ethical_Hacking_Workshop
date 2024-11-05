# Deploy Wazuh Docker in single node configuration

This deployment is defined in the `docker-compose.yml` file with one Wazuh manager containers, one Wazuh indexer containers, and one Wazuh dashboard container. It can be deployed by following these steps: 

1) Increase max_map_count on your host (Linux). This command must be run with root permissions:
```
$ sysctl -w vm.max_map_count=262144
```
2) Run the certificate creation script:
```
$ docker-compose -f generate-indexer-certs.yml run --rm generator
```
3) Start the environment with docker-compose:

- In the foregroud:
```
$ docker-compose up
```
- In the background:
```
$ docker-compose up -d
```

The environment takes several minutes to get up (depending on your Docker host) for the first time since Wazuh Indexer must be started for the first time and the indexes and index patterns must be generated.

# Use active response
yum update
yum install nano
nano /var/ossec/etc/ossec.conf

```
<active-response>
    <command>firewall-drop</command>
    <location>local</location>
    <rules_id>5763</rules_id>
    <timeout>180</timeout>
</active-response>
```

/var/ossec/bin/wazuh-control restart


# Trouble shooting
Currently, when rebooting the docker infrastructure, the Ubuntu agent may not be recognized due to duplicate agent names. To remove the old agent, connect to the Wazuh manager and execute `/var/ossec/bin/manage_agents`.