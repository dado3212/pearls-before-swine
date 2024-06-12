import datetime, requests, re, urllib
from cgi import escape
import json
import urllib3

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

base_url = "http://www.gocomics.com/pearlsbeforeswine/"
start_date = datetime.datetime(2024, 6, 10)
end_date = datetime.datetime(2024, 6, 12)
headers = {"User-Agent":"Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36"}

date = start_date

comics = []

def write_to_file(comics):
	print('Writing')

	t = open('json.txt', 'w')
	t.write(json.dumps(comics))
	t.close()

	print('Done')

last_url = ""

cont = True
try:
	while cont:
		try:
			with requests.Session() as c:
				comic = c.get(base_url + date.strftime('%Y/%m/%d'), verify=False, headers=headers) # initializes the headers, cookies
				small_url = re.search('data-image="(https://assets.amuniversal.com/.*?)"', comic.text).group(1)
				url = small_url

				if (url == last_url):
					cont = False
					write_to_file(comics)
				else:
					comics.append({'url': url, 'small_url': small_url, 'date': date.strftime('%m/%d/%Y')})	
				last_url = url
			
			date = date + datetime.timedelta(days=1)
			if (date > end_date):
				cont = False
				write_to_file(comics)
			else:
				print(date.strftime('%Y/%m/%d'))
		except Exception as e:
			print(e)
			cont = False
			write_to_file(comics)
except:
	write_to_file(comics)

