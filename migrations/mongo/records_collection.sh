#!/usr/bin/env bash

mongosh --host mongo <<EOF
  use default

  db.createCollection("records")
  db.records.createIndex(
      { "expires_at": 1 },
      { expireAfterSeconds: 0 }
  )
  db.runCommand({collMod: "records", changeStreamPreAndPostImages: {enabled: true}})
EOF
