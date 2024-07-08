#!/usr/bin/env bash

mongosh "mongodb://mongo/default?replicaSet=rs0&readPreference=primary" <<EOF
db.records.insertOne({
    transaction_id: "txn12345",
    user_id: "1",
    expires_at: new Date(Date.now() + 60 * 1000) // Define a expiração para 1 minuto a partir de agora
});
EOF
