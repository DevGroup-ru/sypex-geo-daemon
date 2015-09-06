# sypex-geo-daemon

ReactPHP HTTP daemon that resolves GEO information on given IP

## Usage

*Tip:* This daemon works extremely fast with PHP7!

Get the database file from [official SypexGEO site](https://sypexgeo.net/ru/download/).

Start the daemon:

```bash

php server.php --host=0.0.0.0 --port=16001

```

Make your simple requests. 
For example this requests `http://127.0.0.1:16001/?ip=213.180.204.3` gives the following output(prettified for better look):

```json
{
    "city": {
        "id": 524901,
        "lat": 55.75222,
        "lon": 37.61556,
        "name_ru": "\u041c\u043e\u0441\u043a\u0432\u0430",
        "name_en": "Moscow"
    },
    "region": {
        "id": 524894,
        "name_ru": "\u041c\u043e\u0441\u043a\u0432\u0430",
        "name_en": "Moskva",
        "iso": "RU-MOW"
    },
    "country": {
        "id": 185,
        "iso": "RU",
        "lat": 60,
        "lon": 100,
        "name_ru": "\u0420\u043e\u0441\u0441\u0438\u044f",
        "name_en": "Russia"
    },
    "time": "0.000406980515",
    "error": false
}
```
