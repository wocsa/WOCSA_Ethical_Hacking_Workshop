# Install docker or docker Desktop
[Install Docker](https://docs.docker.com/engine/install/)

# Deploy Wazuh Docker in single node configuration

This deployment is defined in the `docker-compose.yml` file with one Wazuh manager containers, one Wazuh indexer containers, and one Wazuh dashboard container. It can be deployed by following these steps: 

1) With Docker Desktop, increase the usable RAM to 8 Gb. Increase max_map_count on your host (Linux). This command must be run with root permissions:
```
$ sysctl -w vm.max_map_count=262144
```
2) Run the certificate creation script:
```
$ docker compose -f generate-indexer-certs.yml run --rm generator
```
3) Start the environment with docker-compose:

- In the foregroud:
```
$ docker compose up
```
- In the background:
```
$ docker compose up -d
```

The environment takes several minutes to get up (depending on your Docker host) for the first time since Wazuh Indexer must be started for the first time and the indexes and index patterns must be generated.

# Connect to the Wazuh pannel
Go to [Wazuh pannel](https://localhost)

The default username is `admin` and the default password is `SecretPassword`.

In order to see your connected agents, you can go to [Server Management]()

# Connect to the kali linux docker
You can connect to it using ssh with the following command: `ssh kali@localhost -p 2222`. The password is also `kali`.

Run a ssh brute force: 
```bash
hydra -l ubuntu -P /rockyou.txt ssh://ubuntu
```

# Use active response
Go to the [Manager Configuration](https://localhost/app/settings#/manager/?tab=configuration) and change the `active-response` section.

```
<active-response>
    <command>firewall-drop</command>
    <location>local</location>
    <rules_id>5763</rules_id>
    <timeout>180</timeout>
</active-response>
```

Save the file and restart the manager.
