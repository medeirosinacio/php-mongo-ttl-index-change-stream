#!/usr/bin/env bash

set -eu

# Set variables
SDDB_USER=user
SDDB_PASS=password
SDDB_DATABASE=default

# replicate set initiate
echo "Checking mongo container..."
until mongosh --host mongo  --eval "print(\"waited for connection\")"
do
    sleep 1
done

echo "Initializing replicaset..."
mongosh --host mongo  <<EOF
    rs.initiate(
      {
          _id: "rs0",
          version: 1,
          members: [
            { _id: 0, host: "mongo:27017"}
          ]
      }
    )
    rs.status()
EOF


echo "Creating admin user: root@root/admin"
mongosh --host mongo  <<EOF
    db.getSiblingDB('admin').createUser(
        {
            user: "root",
            pwd: "root",
            roles: [ { role: "root", db: "admin" } ]
         }
    )
    rs.status()
EOF

echo "Creating normal user: ${SDDB_USER}:${SDDB_PASS}/${SDDB_DATABASE}"
mongosh --host mongo  <<EOF
  use ${SDDB_DATABASE}
  db.createUser(
    {
      user: "${SDDB_USER}",
      pwd: "${SDDB_PASS}",
      roles: [ { role: "dbOwner", db: "${SDDB_DATABASE}" } ]
    }
  )
EOF

echo "Confirm normal user account"
echo "---------------------------------------"
mongosh --eval 'rs.status()' "mongodb://${SDDB_USER}:${SDDB_PASS}@mongo:27017/${SDDB_DATABASE}"
echo "---------------------------------------"
