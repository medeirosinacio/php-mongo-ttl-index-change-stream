#!/usr/bin/env bash

## Need to connect to the mongo primary node
mongosh "mongodb://mongo/default?replicaSet=rs0&readPreference=primary" <<EOF
  db.createCollection("records")
  db.records.createIndex(
      { "expires_at": 1 },
      { expireAfterSeconds: 0 }
  )
  db.runCommand({collMod: "records", changeStreamPreAndPostImages: {enabled: true}})
EOF
