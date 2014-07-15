# nginx config #
```
server {
    listen       80;
    server_name  ~^(?<project>\w+)\.domain.com$;
    root   /data/website/$project/master;

    charset utf-8;
    access_log  /var/log/nginx/projects/$project.master.access.log  main;

    location / {
        index  index.html index.php;
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.(js|css|png|jpg|gif|swf|ico|pdf|mov|fla|zip|rar)$ {
        try_files $uri =404;
    }

    location ~ \.php {
        fastcgi_split_path_info  ^(.+\.php)(.*)$;

        fastcgi_pass   127.0.0.1:9000;
        fastcgi_index  index.php;
        fastcgi_param  MOD_ENV DEVELOPMENT;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        include        fastcgi_params;

        fastcgi_param  PATH_INFO        $fastcgi_path_info;
        fastcgi_param  PATH_TRANSLATED  $document_root$fastcgi_script_name;
    }

    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
}

server {
    listen       80;
    server_name  ~^(?<branch>\w+)\.(?<project>\w+)\.domain.com$;
    root   /data/website/$project/$branch;

    charset utf-8;
    access_log  /var/log/nginx/projects/$project.$branch.access.log  main;

    location / {
        index  index.html index.php;
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.(js|css|png|jpg|gif|swf|ico|pdf|mov|fla|zip|rar)$ {
        try_files $uri =404;
    }

    location ~ \.php {
        fastcgi_split_path_info  ^(.+\.php)(.*)$;

        fastcgi_pass   127.0.0.1:9000;
        fastcgi_index  index.php;
        fastcgi_param  MOD_ENV DEVELOPMENT;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        include        fastcgi_params;

        fastcgi_param  PATH_INFO        $fastcgi_path_info;
        fastcgi_param  PATH_TRANSLATED  $document_root$fastcgi_script_name;
    }

    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
}
```

# prepare #

```
cd /data/website
mkdir deploy-hook-github
cd deploy-hook-github
mkdir .repo
git clone --mirror git@github.com:dishuostec/deploy-hook-github.git .repo
```
