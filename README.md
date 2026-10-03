# web-vulnerabilities-demo
This project uses PHP and SQLite to provide a sandbox environment to learn how hackers exploit website vulnerabilities through first-hand experience.

The website is themed after a simple social media app where users can draft, create, and save posts. 

- On the `vulnerable/` path, vulnerabilities related to SQL Injections, Cross Site Scripting, and IDOR are purposefully embedded, and users are encouraged to try to exploit them to gain unauthorized access to website resources. Progressive hints are included on the landing page. 

- On the `safe/` path, these vulnerabilities are patched, and users can test how the website now prevents those attacks. The landing page contains details about how each flaw is fixed. 

## Running it locally

Requires PHP with `pdo_sqlite`. Clone this repo, and from this folder, run:

```bash
php -S localhost:8000
```

Open <http://localhost:8000/>. A demo login is `guest` / `password`. You can reset the data by deleting `vulnerable/data/` and `safe/data/`. 

### AI Disclosure
I initially came up with an overview of the website including the vulnerabilities to showcase, how to showcase them, and what the pages of each website should look like. From there, I leveraged AI to generate a base framework for me to further develop. While I expanded on this framework, I additionally used AI to assist me with styling and debugging my code. 