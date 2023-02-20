#!/bin/bash
USER_NAME=$1
USER_ID=$2
GROUP_ID=$3

NEW_USER_GROUP_NAME="${USER_NAME}"

if ! id -u "${NEW_USER_GROUP_NAME}" > /dev/null 2>&1; then
  NEW_USER_GROUP_NAME="abc123def456ghi789"
fi

echo "temporary group and user name: ${NEW_USER_GROUP_NAME}"

if ! id -gn "${GROUP_ID}" > /dev/null 2>&1; then
  echo "create new group: ${NEW_USER_GROUP_NAME} with id *${GROUP_ID}*"
  addgroup --gid "${GROUP_ID}" "${NEW_USER_GROUP_NAME}"
fi

if ! id -un "${USER_ID}" > /dev/null 2>&1; then
  echo "create new user: ${NEW_USER_GROUP_NAME} with id *${USER_ID}* for group id *${GROUP_ID}*"
  useradd "${USER_NAME}" -m -l -u "${USER_ID}" -g "${GROUP_ID}"
fi

cypress run --browser chrome

RESULT=$?

chown -R "${USER_ID}:${GROUP_ID}" /results

exit RESULT
