#include <stdio.h>
static void display(int y);

int main(void)
{
	int x = 3;

	display(x);
	return 0;
}


void display(int x)
{
	int i;
	for ( i=0; i<x; ++i ) {
		printf("i is %d.\n", i);
	}
}
