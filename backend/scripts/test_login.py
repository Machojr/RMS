import json
import urllib.request

url = 'http://localhost/rms/backend/index.php/auth/login'
data = json.dumps({'email': 'co@rms.go.tz', 'password': 'co123'}).encode('utf-8')
req = urllib.request.Request(url, data=data, headers={'Content-Type': 'application/json'}, method='POST')
with urllib.request.urlopen(req) as response:
    print(response.read().decode('utf-8'))
