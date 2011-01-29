# What is FormoJS? #

FormoJS is a Kohana module which adds live validation feedback to forms built with [Formo](http://github.com/bmidget/kohana-formo) through Javascript and asynchronous requests.

# What FormoJS is not #

FormoJS is not a replacement for server-side Formo validation. While it is possible to do validation in javascript on the client side, the nature of the web is such that client-side validation should never be relied upon.

# Demo #

A demo application can be found at <http://kohana.cowuu.be/formojs/>.

# Developing #

When committing changes to any of the static javascript files, please also commit an updated compressed version using the YUI compressor.

The following code saved in .git/hooks/pre-commit will automatically update the compressed files:

	#!/bin/bash

	if git rev-parse --verify HEAD >/dev/null 2>&1
	then
		against=HEAD
	else
		# Initial commit: diff against an empty tree object
		against=4b825dc642cb6eb9a060e54bf8d69288fbee4904
	fi

	git diff-index --name-only --cached $against js/formojs/ | grep -ve "-yc\.js" | sed -e 's/\.js$//' | xargs sh -c 'yc -o "$1-yc.js" "$1.js"; git add "$1-yc.js"'
	
**NOTE:** this assumes that the command `yc` will invoke the YUI compressor. The command `yc` can be replaced with `java -jar path_to/yuicompressor-version.jar`.
 