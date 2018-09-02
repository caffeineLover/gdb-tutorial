#include <stdio.h>
#include <string.h>
#include "fgets.h"


int TakeGuess(const char *word)
{
	char buffer[255];

	fgets(buffer, 255, stdin);

	if (strcmp(buffer, word))
			return TRUE;

	return FALSE;
}
