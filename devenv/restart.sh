#! /usr/bin/env bash

PROG="$0"
BASE_URL=http://127.0.0.1/

cd `dirname "$0"`
docker build -t clerk/clerk-magento . || exit 1
echo "=============================================================="
echo "||        DOCKER IMAGE SUCCESSFULLY REBUILT                 ||"
echo "=============================================================="
echo ""

usage() {
  echo "Usage:" >&2
  echo "$PROG [-b BASE_URL]" >&2
  echo "" >&2
  echo "Options:" >&2
  echo "   -b | --base-url                     The base URL (default: http://mymagentostore.com/)" >&2
  echo "   -h | --help                         Print this help" >&2
}

while [[ $# > 0 ]]; do
  case "$1" in
    -b|--base-url)
      case "$2" in
      */)
        BASE_URL="$2"
        ;;
      *)
        BASE_URL="$2/"
        ;;
      esac
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option '$1'." >&2
      echo ""
      usage
      exit 2
    ;;
  esac
  shift
done

ensure() {
  if [ -z "$2" ]; then
    echo "Missing option $1."
    echo ""
    usage
    exit 1
  fi
}

ensure "-b" "$BASE_URL"

docker stop clerk-magento > /dev/null 2>&1 || true
docker rm clerk-magento > /dev/null 2>&1 || true

echo "           BASE_URL: $BASE_URL"
echo ""

docker run -p 80:80 \
  -v "`pwd`/..":/var/www/htdocs/.modman/clerk-magento \
  -e BASE_URL=$BASE_URL \
  -d \
  --name clerk-magento \
  -t clerk/clerk-magento
