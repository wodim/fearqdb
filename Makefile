compiled-clean:
	rm templates/compiled/* -vf

messages-extract:
	rm templates/compiled/* -vf
	find -type f -iname "*.php" | xgettext --keyword=_ --from-code="UTF-8" -f - -o messages.po
	find -type f -iname "*.html" | xgettext --keyword=_ --from-code="UTF-8" --language=Python -f - -j -o messages.po
