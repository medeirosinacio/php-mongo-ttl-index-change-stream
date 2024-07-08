#!/usr/bin/env bash

mongosh "mongodb://mongo/default?replicaSet=rs0&readPreference=primary" <<EOF
db.getCollectionNames().forEach(c => db.getCollection(c).drop())
EOF
