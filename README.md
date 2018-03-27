skippy.is automation

#DEPRECATED
This setup has been updated and documented at my [micropub](https://github.com/skpy/micropub) repo.  Check that for the current setup.

---
This is a slightly sanitized version of the scripts I've put together to make posting to [skippy.is](https://skippy.is/) easy to do from my phone.

## Requirements
* a web server running PHP with the GD extension enabled
* ImageMagick installed (for the `identify` command)
* rsync
* [Hugo](http://gohugo.io/)
* [Python Twitter](https://github.com/sixohsix/twitter) client

The server on which you run these tools need not be the server that actually serves the generated site.

## Workflow
Load the HTML page in a browser. You can serve the HTML page from a web server, or you could save it to your phone and run it from there. All it really needs to do is HTTP POST to the PHP script. Someone more sophisticated than myself could even create a native mobile application for this.

The "token" hidden field in the form is an optional safety parameter. I didn't want to jump through any authorization logic, or other things that can be hard to do from a phone browser.  But since this script will be exposed to the public Internet, it should have some modicum of safety built in.

The theory is: if I have the HTML file **only** on my phone, and not on my web server, I can be relatively confident that any POSTs to the PHP script that contain the token are actually from me.  Sure, someone could attempt a brute force attack against this script; but in that case I have other problems.

When a POST is made, the PHP script performs some basic sanity checks. It intentionally spits out a generic misleading message for all failure conditions. If things aren't working correctly, check the PHP logs.

Assuming everything is okay, the uploaded image is reduced in size and saved to disk. Then a Markdown file is created with the front-matter YAML that I use.

The `is.sh` script is invoked from cron every 10 minutes. If a new posts has been created, it copies the image file to the `/static/images` directory in my Hugo source directory, and the Markdown file to the `/content` directory.  It then runs Hugo to regenerate the site.  Next it rsyncs the site to where I actually host it.  And finally it tweets a message to the world.

