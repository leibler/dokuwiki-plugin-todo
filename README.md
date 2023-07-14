DokuWiki Plugin ToDo
=====

see https://www.dokuwiki.org/plugin:todo


Notes for Collaborators:
====
To prepare a new release:
- Take note of latest date tag, i.e. "2023-05-17" and edit "latest" release to be based on that instead of 'latest' tag.
- Move `latest` tag to current commit and add current date tag
```
# commit changes etc.
git push origin
git tag `date +%F`
git tag --force latest
git push origin `date +%F`
# this should only update the tag, not any commits...
git push -f origin latest
```
- Create a new release based on `latest` tag.
