<!-- !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! -->
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html lang="en">
	<head>
	<title>Peter's gdb Tutorial: Memory Layout And The Stack </title>
	<link rel=STYLESHEET type="text/css" href="./inclusions/style.css" title="Style" />
	</head>
	<body>

	<img src="inclusions/images/gnu-head.png" align="right" alt="" /><h1>Using GNU's GDB Debugger</h1><h1>Memory Layout And The Stack</h1><h4>By Peter Jay Salzman</h4><hr /><table width="100%"><tr><td align="left">Previous: <a href="01-Introduction.php">Introduction</a></td><td align="right">Next: <a href="02b-Debugging_With_Your_Brain.php">Debugging With Your Brain</a></td></tr></table><hr />


<br /><br /><a name="wherearewegoingtogo"><h2>Where Are We Going To Go?</h2></a>

<p>To effectively learn how to use GDB, you must understand frames, which are also called stack frames because they're the
frames that comprise the stack.  To learn about the stack, we need to learn about the memory layout of an executing program.
The discussion will mainly be theoretical, but to keep things interesting we'll conclude the chapter with an example of the
stack and stack frames using GDB.</p>

<p>The material learned in this chapter may seem rather theoretical, but it does serve a few very useful purposes:</p>
<ol>
<li>Understanding the stack is absolutely necessary for using a symbolic debugger like GDB.</li>
<li>Knowing the memory layout of a process will help us understand what exactly a segmentation fault (or segfault) is, and
	why they happen (or sometimes, more importantly) <i>don't</i> happen when they should.  In brief, segfaults are the most
	common immediate cause for a program to bomb.</li>
<li>A knowledge of a program's memory space can often allow us to figure out the location of well-hidden bugs without the use
	of <tt>print()</tt> statements, a compiler or even GDB!  In the next section, which is a guest written piece by one my
	friends, Mark Kim, we'll see some real Sherlock Holmes style sleuthing.  Mark homes in on a well hidden bug in somewhat
	lengthy code.  It only took him about 5 or 10 minutes, and all he did was look at the program and use his knowledge of
	how a program's memory space works.  It's really impressive!</li>
</ol>

<p>So without futher ado, let's take a look at how programs are laid out in memory.</p>





<br /><br /><a name="virtualmemory(vm)"><h2>Virtual Memory (VM)</h2></a>

<p>Whenever a process is created, the kernel provides a chunk of physical memory which can be located anywhere at all.
However, through the magic of virtual memory (VM), the process believes it has all the memory on the computer.  You might
have heard "virtual memory" in the context of using hard drive space as memory when RAM runs out.  That's called virtual
memory too, but is largely unrelated to what we're talking about.  The VM we're concerned with consists of the following
principles:</p>

<ol>
<li>Each process is given physical memory called the process's <i>virtual memory space</i>.</li>
<li>A process is unaware of the details of its physical memory (i.e. where it physically resides).  All the process knows is
	how big the chunk is and that its chunk begins at address 0.</li>
<li>Each process is unaware of any other chunks of VM belonging to other processes.</li>
<li>Even if the process <I>did</I> know about other chunks of VM, it's physically prevented from accessing that memory.</li>
</ol>

<p>Each time a process wants to read or write to memory, its request must be translated from a VM address to a physical
memory address.  Conversely, when the kernel needs to access the VM of a process, it must translate a physical memory address
into a VM address.  There are two major issues with this:</p>

<ol>
<li>Computers constantly access memory, so translations are <i>very</i> common; they must be lightning fast.</li>
<li>How can the OS <I>ensure</I> that a process doesn't trample on another process's VM?</li>
</ol>

<p>The answer to both questions lies in the fact that the OS doesn't manage VM by itself; it gets help from the CPU.  Many
CPUs contain a device called an MMU: a memory management unit.  The MMU and the OS are jointly responsible for managing VM,
translating between virtual and physical addresses, enforcing permissions on which processes are allowed to access which
memory locations, and enforcing read/write permissions on sections of a VM space, even for the process that owns that
space.</p>

<p>It used to be the case that Linux could only be ported to architectures that had an MMU (so Linux wouldn't run on, say, an
x286).  However, in 1998, Linux was ported to the 68000 which had no MMU.  This paved the way for embedded Linux and Linux on
devices such as the Palm Pilot.</p>


<h4 class="exercise">Exercises</h4><ol class="exercise"><li>Read a short Wikipedia blurb on the <a href="http://en.wikipedia.org/wiki/Memory_management_unit">MMU</a></li>
<li>Optional: If you want to know more about VM, here's a <a href="http://en.wikipedia.org/wiki/Virtual_memory">link</a>.  This
	is <I>much</I> more than you need to know.</li>
</ol>






<br /><br /><a name="memorylayout"><h2>Memory Layout</h2></a>

<p>That's how VM works.  For the most part, each process's VM space is laid out in a similar and predictable manner:</p>

<p style="text-align: center; margin-top: 3em; margin-bottom: 3em">
<table border="1" cellpadding="8" width="85%" style="margin-left: auto; margin-right: auto; font-size: x-small">
<tr>
	<td style="border-width: 0pt">High Address</td>
	<td align="center">Args and env vars</td>
	<td style="border-width: 0pt">Command line arguments and environment variables</td>
</tr><tr>
	<td></td>
	<td align="center">Stack<BR>|<BR>V</td>
	<td style="border-width: 0pt"></td>
</tr><tr>
	<td style="border-width: 0pt"></td>
	<td align="center"><BR>Unused memory<BR><BR></td>
	<td style="border-width: 0pt"></td>
</tr><tr>
	<td style="border-width: 0pt"></td>
	<td align="center">^<BR>|<BR>Heap</td>
	<td style="border-width: 0pt"></td>
</tr><tr>
	<td style="border-width: 0pt"></td>
	<td align="center">Uninitialized Data Segment (bss)</td>
	<td style="border-width: 0pt">Initialized to zero by <tt>exec</tt>.</td>
</tr><tr>
	<td style="border-width: 0pt"></td>
	<td align="center">Initialized Data Segment</td>
	<td style="border-width: 0pt">Read from the program file by <tt>exec</tt>.</td>
</tr><tr>
	<td style="border-width: 0pt">Low Address</td>
	<td align="center">Text Segment</td>
	<td style="border-width: 0pt">Read from the program file by <tt>exec</tt>.</td>
</tr>
</table>
</p>

<ul>
<li><b>Text Segment:</b> The text segment contains the actual code to be executed.  It's usually sharable, so multiple
	instances of a program can share the text segment to lower memory requirements.  This segment is usually marked read-only
	so a program can't modify its own instructions.</li>
<li><b>Initialized Data Segment:</b> This segment contains global variables which are initialized by the programmer.</li>
<li><b>Uninitialized Data Segment:</b> Also named "bss" (block started by symbol) which was an operator used by an old
	assembler.  This segment contains uninitialized global variables.  All variables in this segment are initialized to 0 or
	NULL pointers before the program begins to execute.</li>
<li><b>The stack:</b> The stack is a collection of stack frames which will be described in the next section.  When a new
	frame needs to be added (as a result of a newly called function), the stack grows downward.
<li><b>The heap:</b> Most dynamic memory, whether requested via C's <tt>malloc()</tt> and friends or C++'s <tt>new</tt> is
	doled out to the program from the heap.  The C library also gets dynamic memory for its own personal workspace from the
	heap as well.  As more memory is requested "on the fly", the heap grows upward.
</ul>

<p>Given an object file or an executable, you can determine the size of each section (realize we're not talking about memory
layout; we're talking about a disk file that will eventually be resident in memory).  Given <?
code('02/hello_world-1/hello_world-1.c') ?>, <a href="./code/02/hello_world-1/Makefile">Makefile</a>:</p>

<pre class="code">1   // hello_world-1.c
2   
3   #include &lt;stdio.h&gt;
4   
5   int main(void)
6   {
7      printf("hello world\n");
8   
9      return 0;
10  }
</pre>
<p>compile it and link it separately with:</p>

<pre class="demo">
   $ gcc -W -Wall -c hello_world-1.c
   $ gcc -o hello_world-1  hello_world-1.o
</pre>

<p>You can use the <tt>size</tt> command to list out the size of the various sections:</p>

<pre class="demo">
   $ size hello_world-1 hello_world-1.o 
   text   data   bss    dec   hex   filename
    916    256     4   1176   498   hello_world-1
     48      0     0     48    30   hello_world-1.o
</pre>

<p>The data segment is the initialized and uninitialized segments combined.  The dec and hex sections are the file size in
decimal and hexadecimal format respectively.<p>

<p>You can also get the size of the sections of the object file using "<tt>objdump -h</tt>" or "<tt>objdump -x</tt>".</p>

<pre class="demo">
   $ objdump -h hello_world-1.o 
   
   hello_world-1.o:     file format elf32-i386
   
   Sections:
   Idx Name          Size      VMA       LMA       File off  Algn
     0 .text         00000023  00000000  00000000  00000034  2**2
                     CONTENTS, ALLOC, LOAD, RELOC, READONLY, CODE
     1 .data         00000000  00000000  00000000  00000058  2**2
                     CONTENTS, ALLOC, LOAD, DATA
     2 .bss          00000000  00000000  00000000  00000058  2**2
                     ALLOC
     3 .rodata       0000000d  00000000  00000000  00000058  2**0
                     CONTENTS, ALLOC, LOAD, READONLY, DATA
     4 .note.GNU-stack 00000000  00000000  00000000  00000065  2**0
                     CONTENTS, READONLY
     5 .comment      0000001b  00000000  00000000  00000065  2**0
                     CONTENTS, READONLY
</pre>





<h4 class="exercise">Exercises</h4><ol class="exercise"><li>The <tt>size</tt> command didn't list a stack or heap segment for hello_world or hello_world.o.  Why do you think that
	is?</li>
<li>There are no global variables in hello_world-1.c.  Give an explanation for why <tt>size</tt> reports that the data and
	bss segments have zero length for the object file but non-zero length for the executable.</li>
<li><tt>size</tt> and <tt>objdump</tt> report different sizes for the text segment.  Can you guess where the discrepancy
	comes from?  Hint: How big is the discrepancy?  See anything of that length in the source code?</li>
<li>Optional: Read this <a href="http://en.wikipedia.org/wiki/Object_file_format">link</a> about object file formats.</li>
</ol>








<br /><br /><a name="stackframesandthestack"><h2>Stack Frames And The Stack</h2></a>

<p>You just learned about the memory layout for a process.  One section of this memory layout is called the <i>stack</i>,
which is a collection of <i>stack frames</i>.  Each stack frame represents a function call.  As functions are called, the
number of stack frames increases, and the stack grows.  Conversely, as functions return to their caller, the number of stack
frames decreases, and the stack shrinks.  In this section, we learn what a stack frame is.  A very detailed explanation <?
a('en.wikipedia.org/wiki/Stack_frame','here') ?>, but we'll go over what's important for our purposes.</p>

<p>A program is made up of one or more functions which interact by calling each other.  Every time a function is called, an
area of memory is set aside, called a stack frame, for the new function call.  This area of memory holds some crucial
information, like:</p>

	<ol>
	<li>Storage space for all the automatic variables for the newly called function.</li>
	<li>The <span class="tip" title="Actually, memory address">line number</span> of the calling function to return to
		when the called function returns.</li>
	<li>The arguments, or parameters, of the called function.</li>
	</ol>

<p>Each function call gets its own stack frame.  Collectively, all the stack frames make up the <span class="tip" title="Or
just 'stack'">call stack</span>.  We'll use <a href="./code/02/hello_world-2/hello_world-2.c">hello_world-2.c</a> for the next example.</p>

<pre class="code">1   #include &lt;stdio.h&gt;
2   void first_function(void);
3   void second_function(int);
4   
5   int main(void)
6   {
7      printf("hello world\n");
8      first_function();
9      printf("goodbye goodbye\n");
10  
11     return 0;
12  }
13  
14  
15  void first_function(void)
16  {
17     int imidate = 3;
18     char broiled = 'c';
19     void *where_prohibited = NULL;
20  
21     second_function(imidate);
22     imidate = 10;
23  }
24  
25  
26  void second_function(int a)
27  {
28     int b = a;
29  }
</pre>

<table>
<tr valign="top">
<td style="width: 65%">
<p>When the program starts, there's one stack frame, belonging to <tt>main()</tt>.  Since <tt>main()</tt> has no automatic
variables, no parameters, and no function to return to, the stack frame is uninteresting. Here's what the stack looks like
just before the call to <tt>first_function()</tt> is made.</p>

</td>
<td>

	<p style="text-align: center; margin-top: 2em; margin-bottom: 2em">
	<table border="1" cellpadding="6" style="width:90%; margin-left: auto; margin-right: auto; font-size: small">
	<tr>
		<td style="background-color: red;">
		Frame for <tt>main()</tt><br />
		</td>
	</tr>
	</table>
	</p>

</td>
</tr>
</table>




<table>
<tr>
<td style="width: 65%">
<p>When the call to <tt>first_function()</tt> is made, unused stack memory is used to create a frame for
<tt>first_function()</tt>.  It holds four things: storage space for an int, a char, and a void *, and the line to return to
within <tt>main()</tt>.  Here's what the call stack looks like right before the call to <tt>second_function()</tt> is
made.</p>

</td>
<td>

	<p style="text-align: center; margin-top: 2em; margin-bottom: 2em">
	<table border="1" cellpadding="6" style="width: 90%; margin-left: auto; margin-right: auto; font-size: small">
	<tr>
		<td style="background-color: red;">
		Frame for <tt>main()</tt><br />
		</td>
	</tr><tr>
		<td style="background-color: orange;">
		Frame for <tt>first_function()</tt><br />
		<span style="margin-left: 4ex">Return to <tt>main()</tt>, line 9</span><br />
		<span style="margin-left: 4ex">Storage space for an int</span><br />
		<span style="margin-left: 4ex">Storage space for a char</span><br />
		<span style="margin-left: 4ex">Storage space for a void *</span><br />
		</td>
	</tr>
	</table>
	</p>
</td>
</tr>
</table>




<table>
<tr>
<td style="width: 65%">
<p>When the call to <tt>second_function()</tt> is made, unused stack memory is used to create a stack frame for
<tt>second_function()</tt>.  The frame holds 3 things: storage space for an int and the current address of execution within
<tt>second_function()</tt>.  Here's what the stack looks like right before <tt>second_function()</tt> returns.</p>

</td>
<td>

	<p style="text-align: center; margin-top: 2em; margin-bottom: 2em">
	<table border="1" cellpadding="6" width="90%" style="margin-left: auto; margin-right: auto; font-size: small">
	<tr>
		<td style="background-color: red">
		Frame for <tt>main()</tt><br />
		</td>
	</tr><tr>
		<td style="background-color: orange">
		Frame for <tt>first_function()</tt>:<br />
		<span style="margin-left: 4ex">Return to <tt>main()</tt>, line 9</span><br />
		<span style="margin-left: 4ex">Storage space for an int</span><br />
		<span style="margin-left: 4ex">Storage space for a char</span><br />
		<span style="margin-left: 4ex">Storage space for a void *</span><br />
		</td>
	</tr><tr>
		<td style="background-color: yellow">
		Frame for <tt>second_function()</tt>:<br />
		<span style="margin-left: 4ex">Return to <tt>first_function()</tt>, line 22</span><br />
		<span style="margin-left: 4ex">Storage space for an int</span><br />
		<span style="margin-left: 4ex">Storage for the int parameter named <tt>a</tt></span><br />
		</td>

	</tr>
	</table>
	</p>
</td>
</tr>
</table>


<table>
<tr>
<td style="width: 65%">

<p>When <tt>second_function()</tt> returns, its frame is used to determine where to return to (line 22 of
<tt>first_function()</tt>), then deallocated and returned to stack.  Here's what the call stack looks like after
<tt>second_function()</tt> returns:</p>

</td>
<td>

	<p style="text-align: center; margin-top: 2em; margin-bottom: 2em">
	<table border="1" cellpadding="6" width="90%" style="margin-left: auto; margin-right: auto; font-size: small">
	<tr>
		<td style="background-color: red;">
		Frame for <tt>main()</tt><br />
		</td>
	</tr><tr>
		<td style="background-color: orange">
		Frame for <tt>first_function()</tt>:<br />
		<span style="margin-left: 4ex">Return to <tt>main()</tt>, line 9</span><br />
		<span style="margin-left: 4ex">Storage space for an int</span><br />
		<span style="margin-left: 4ex">Storage space for a char</span><br />
		<span style="margin-left: 4ex">Storage space for a void *</span><br />
		</td>
	</tr>
	</table>
	</p>

</td>
</tr>
</table>





<table>
<tr>
<td style="width: 65%">
<p>When <tt>first_function()</tt> returns, its frame is used to determine where to return to (line 9 of <tt>main()</tt>),
then deallocated and returned to the stack.  Here's what the call stack looks like after <tt>first_function()</tt>
return:</p>

</td>
<td>

	<p style="text-align: center; margin-top: 2em; margin-bottom: 2em">
	<table border="1" cellpadding="6" width="90%" style="margin-left: auto; margin-right: auto; font-size: small">
	<tr>
		<td style="background-color: red;">
		Frame for <tt>main()</tt><br />
		</td>
	</tr>
	</table>
	</p>

</td>
</tr>
</table>


<p>And when <tt>main()</tt> returns, the program ends.</p>


<h4 class="exercise">Exercises</h4><ol class="exercise"><li>Suppose a program makes 5 function calls.  How many frames should be on the stack?</li>
<li>We saw that the stack grows linearly downward, and that when a function returns, the last frame on the stack is
	deallocated and returned to unused memory.  Is it possible for a frame somewhere in the <I>middle</I> of the stack to be
	returned to unused memory?  If it did, what would that mean about the running program?</li>
<li>Can a <tt>goto()</tt> statement cause frames in the middle of the stack to be deallocated?  The answer is no, but why?</li>
<li>Can <tt>longjmp()</tt> cause frames in the middle of the stack to be deallocated?</li>
</ol>






<br /><br /><a name="thesymboltable"><h2>The Symbol Table</h2></a>

<p>A <i>symbol</i> is a variable or a function.  A <i>symbol table</i> is exactly what you think: it's a table of variables
and functions within an executable.  Normally, symbol tables contain only memory addresses of symbols, since computers don't
use (or care) what we name variables and functions.</p>

<p>But in order for GDB to be useful to us, it needs to be able to refer to variable and function names, not their addresses.
Humans use names like <tt>main()</tt> or <tt>i</tt>.  Computers use addresses like <tt>0x804b64d</tt> or <tt>0xbffff784</tt>.
To that end, we can compile code with "debugging information" which tells GDB two things:</p>

<ol>
<li>How to associate the address of a symbol with its name in the source code.</li>
<li>How to associate the address of a machine code with a line of source code.</li>
</oL>

<p>A symbol table with this extra debugging information is called an <I>augmented</I> or <I>enhanced</I> symbol table.
Because gcc and GDB run on so many different platforms, there are many different formats for debugging information:</p>

<ul>
<li><b>stabs:</b>  The format used by DBX on most BSD systems.</li>
<li><b>coff:</b>   The format used by SDB on most System V systems before System V Release 4.</li>
<li><b>xcoff:</b>  The format used by DBX on IBM RS/6000 systems.</li>
<li><b>dwarf:</b>  The format used by SDB on most System V Release 4 systems.</li>
<li><b>dwarf2:</b> The format used by DBX on IRIX 6.</li>
<li><b>vms:</b>    The format used by DEBUG on VMS systems.</li>
</ul>

<!-- Typo found by Carlos Rivera -->
<p>In addition to debugging formats, GDB understands enhanced variants of these formats that allow it to make use of GNU
extensions.  Debugging an executable with a GNU enhanced debugging format with something other than GDB could result in
anything from it working correctly to the debugger crashing.</p>

<p>Don't let all these formats scare you: in the next section, I'll show you that GDB automagically picks whatever format is
best for you.  And for the .1% of you that need a different format, you're already knowledgeable enough to make that
decision.</p>






<br /><br /><a name="preparinganexecutablefordebugging"><h2>Preparing An Executable For Debugging</h2></a>

<p>If you plan on debugging an executable, a corefile resulting from an executable, or a running process, you <b>must</b>
compile the executable with an enhanced symbol table.  To generate an enhanced symbol table for an executable, we must compile
it with gcc's <tt>-g</tt> option:</p>

<pre class="demo">
   gcc -g -o filename filename.c
</pre>

<p>As previously discussed, there are many different debugging formats.  The actual meaning of <tt>-g</tt> is to produce
debugging information in the native format for your system.</p>

<p>As an alternative to <tt>-g</tt>, you can also use gcc's <tt>-ggdb</tt> option:</p>

<pre class="demo">
   gcc -ggdb -o filename filename.c
</pre>

</p>which produces debugging information in the most expressive format available, including the GNU enhanced variants
previously discussed.  I believe this is probably the option you want to use in most cases.</p>

<p>You can also give a numerical argument to <tt>-g</tt>, <tt>-ggdb</tt> and all the other debugging format options, with 1
being the least amount of information and 3 being the most.  Without a numerical argument, the debug level defaults to 2.  By
using <tt>-g3</tt> you can even access preprocessor macros, which is really nice.  I suggest you always use <tt>-ggdb3</tt> to
produce an enhanced symbol table.</p>

<p>Debugging information compiled into an executable will <i>not</i> be read into memory unless GDB loads the executable.
This means that executables with debug information will not run any slower than executables without debug information (a
common misconception).  While it's true that debugging executables take up more disk space, the executable will not have a
larger "memory footprint" unless it's from within GDB.  Similarly, executable load time will be nearly the same, again, unless
you run the debug executable from within GDB.</p>

<p>One last comment.  It's certainly possible to perform compiler optimizations on an executable which has an augmented symbol
table, in other words: <tt>gcc -g -O9 try1.c</tt>.  In fact, GDB is one of the few symbolic debuggers which will generally do
quite well debugging optimized executables.  However, you should generally turn off optimizations when debugging an executable
because there are situations that will confuse GDB.  Variables may get optimized out of existence, functions may get inlined,
and more things may happen that may or may not confuse gdb.  To be on the safe side, turn off optimization when you're
debugging a program.</p>


<h4 class="exercise">Exercises</h4><ol class="exercise"><li>Run "<tt>strip --only-keep-debug try1</tt>".  Look at the file size of <b>try1</b>.  Now run "<tt>strip --strip-debug
	try1</tt> and look at the file size.  Now run <tt>strip --strip-all try1</tt> and look at the file size.  Can you guess
	what's happening?  If not, your punishment is to read "man strip", which makes for some provocative reading.</li>
<li>You stripped all the unnecessary symbols from <b>try1</b> in the previous exercise.  Re-run the program to make sure it
	works.  Now run "<tt>strip --remove-section=.text try1</tt>" and look at the file length.  Now try to run <b>try1</b>.
	What do you suppose is going on?</li>
<li>Read this <a href="http://en.wikipedia.org/wiki/Symbol_table">link</a> about symbol tables (it's short).</li>
<li>Optional: Read this <a href="http://en.wikipedia.org/wiki/COFF">link</a> about the COFF object file format.</li>
</ol>







<br /><br /><a name="investigatingthestackwithgdb"><h2>Investigating The Stack With GDB</h2></a>

<p>We'll look at the stack again, this time, using GDB.  You may not understand all of this since you don't know about
breakpoints yet, but it should be intuitive.  Compile and run <a href="./code/02/try1.c">try1.c</a>:</p>

<pre class="code">
   1    #include&lt;stdio.h&gt;
   2    static void display(int i, int *ptr);
   3    
   4    int main(void) {
   5       int x = 5;
   6       int *xptr = &x;
   7       printf("In main():\n");
   8       printf("   x is %d and is stored at %p.\n", x, &amp;x);
   9       printf("   xptr points to %p which holds %d.\n", xptr, *xptr);
   10      display(x, xptr);
   11      return 0;
   12   }
   13   
   14    void display(int z, int *zptr) {
   15    	printf("In display():\n");
   16       printf("   z is %d and is stored at %p.\n", z, &amp;z);
   17       printf("   zptr points to %p which holds %d.\n", zptr, *zptr);
   18   }
</pre>

<p>Make sure you understand the output before continuing with this tutorial.  Here's what I see:</p>

<pre class="demo">
   $ ./try1 
   In main():
      x is 5 and is stored at 0xbffff948.
      xptr points to 0xbffff948 which holds 5.
   In display():
      z is 5 and is stored at 0xbffff924.
      zptr points to 0xbffff948 which holds 5.
</pre>

<p>You debug an executable by invoking GDB with the name of the executable.  Start a debugging session with <tt>try1</tt>.
You'll see a rather verbose copyright notice:</p>

<pre class="demo">
   $ gdb try1
   GNU gdb 6.1-debian
   Copyright 2004 Free Software Foundation, Inc.
   GDB is free software, covered by the GNU General Public License, and you are
   welcome to change it and/or distribute copies of it under certain conditions.
   Type "show copying" to see the conditions.
   There is absolutely no warranty for GDB.  Type "show warranty" for details.
   
   (gdb) 
</pre>

<p>The <tt>(gdb)</tt> is GDB's prompt.  It's now waiting for us to input commands.  The program is currently not running; to
run it, type <tt>run</tt>.  This runs the program from inside GDB:</p>

<pre class="demo">
   (gdb) run
   Starting program: try1 
   In main():
      x is 5 and is stored at 0xbffffb34.
      xptr points to 0xbffffb34 which holds 5.
   In display():
      z is 5 and is stored at 0xbffffb10.
      zptr points to 0xbffffb34 which holds 5.
   
   Program exited normally.
   (gdb) 
</pre>

<p>Well, the program ran.  It was a good start, but frankly, a little lackluster.  We could've done the same thing by running
the program ourself.  But one thing we <I>can't</I> do on our own is to pause the program in the middle of execution and take
a look at the stack.  We'll do this next.</p>

<p>You get GDB to pause execution by using <I>breakpoints</I>.  We'll cover breakpoints later, but for now, all you need to
know is that when you tell GDB <tt>break 5</tt>, the program will pause at line <tt>5</tt>.  You may ask: does the program
execute line 5 (pause between 5 and 6) or does the program not execute line 5 (pause between 4 and 5)?  The answer is that
line 5 is not executed.  Remember these principles:</p>

<ol>
<li><tt>break 5</tt> means to pause at line 5.</li>
<li>This means GDB pauses between lines 4 and 5.  Line 4 has executed.  Line 5 has not.</li>
</ol>

<p>Set a breakpoint at line 10 and rerun the program:</p>

<pre class="demo">
   (gdb) break 10
   Breakpoint 1 at 0x8048445: file try1.c, line 10.
   (gdb) run
   Starting program: try1 
   In main():
      x is 5 and is stored at 0xbffffb34.
      xptr holds 0xbffffb34 and points to 5.
   
   Breakpoint 1, main () at try1.c:10
   10         display(x, xptr);
</pre>

<p>We set a breakpoint at line 10 of file try1.c.  GDB told us this line of code corresponds to memory address
<tt>0x8048445</tt>.  We reran the program and got the first 2 lines of output.  We're in <tt>main()</tt>, sitting before line
10.  We can look at the stack by using GDB's <tt>backtrace</tt> command:</tt></p>

<pre class="demo">
   (gdb) backtrace
   #0  main () at try1.c:10
   (gdb) 
</pre>

<p>There's one frame on the stack, numbered 0, and it belongs to <tt>main()</tt>.  If we execute the next line of code, we'll
be in <tt>display()</tt>.  From the previous section, you should know exactly what should happen to the stack: another frame
will be added to the bottom of the stack.  Let's see this in action.  You can execute the next line of code using GDB's
<tt>step</tt> command:</p>


<pre class="demo">
   (gdb) step
   display (z=5, zptr=0xbffffb34) at try1.c:15
   15              printf("In display():\n");
   (gdb) 
</pre>

<p>Look at the stack again, and make sure you understand everything you see:</p>

<pre class="demo">
   (gdb) backtrace
   #0  display (z=5, zptr=0xbffffb34) at try1.c:15
   #1  0x08048455 in main () at try1.c:10
</pre>

<p>Some points to note:</p>

<ul>
<li>We now have two stack frames, frame 1 belonging to <tt>main()</tt> and frame 0 belong to <tt>display()</tt>.</li>
<li>Each frame listing gives the arguments to that function.  We see that <tt>main()</tt> took no arguments, but
	<tt>display()</tt> did (and we're shown the value of the arguments).</li>
<li>Each frame listing gives the line number that's currently being executed within that frame.  Look back at the source
	code and verify you understand the line numbers shown in the backtrace.</li>
<li>Personally, I find the numbering system for the frame to be confusing.  I'd prefer for <tt>main()</tt> to remain frame 0,
	and for additional frames to get higher numbers.  But this is consistent with the idea that the stack grows "downward".
	Just remember that the lowest numbered frame is the one belonging to the most recently called function.</li>
</ul>

<p>Execute the next two lines of code:</p>

<pre class="demo">
   (gdb) step
   In display():
   16         printf("   z is %d and is stored at %p.\n", z, &amp;z);
   (gdb) step
      z is 5 and is stored at 0xbffffb10.
   17         printf("   zptr holds %p and points to %d.\n", zptr, *zptr);
</pre>

<p>Recall that the frame is where automatic variables for the function are stored.  Unless you tell it otherwise, GDB is
always in the context of the frame corresponding to the currently executing function.  Since execution is currently in
<tt>display()</tt>, GDB is in the context of frame 0.  We can ask GDB to tell us which frame its context is in by giving the
<tt>frame</tt> command without arguments:</p>

<pre class="demo">
   (gdb) frame
   #0  display (z=5, zptr=0xbffffb34) at try1.c:17
   17         printf("   zptr holds %p and points to %d.\n", zptr, *zptr);
</pre>

<p>I didn't tell you what the word "context" means; now I'll explain.  Since GDB's context is in frame 0, we have access to
all the local variables in frame 0.  Conversely, we don't have access to automatic variables in any other frame.  Let's
investigate this.  GDB's <tt>print</tt> command can be used to give us the value of any variable within the current frame.
Since <tt>z</tt> and <tt>zptr</tt> are variables in <tt>display()</tt>, and GDB is currently in the frame for
<tt>display()</tt>, we should be able to print their values:</p>

<pre class="demo">
   (gdb) print z
   $1 = 5
   (gdb) print zptr
   $2 = (int *) 0xbffffb34
</pre>

<p>But we do <I>not</I> have access to automatic variables stored in other frames.  Try to look at the variables in
<tt>main()</tt>, which is frame 1:

<pre class="demo">
   (gdb) print x
   No symbol "x" in current context.
   (gdb) print xptr
   No symbol "xptr" in current context.
</pre>

<p>Now for magic.  We can tell GDB to switch from frame 0 to frame 1 using the <tt>frame</tt> command with the frame number as
an argument.  This gives us access to the variables in frame 1.  As you can guess, after switching frames, we won't have
access to variables stored in frame 0.  Follow along:</p>

<pre class="demo">
   (gdb) frame 1                           &lt;--- switch to frame 1
   #1  0x08048455 in main () at try1.c:10
   10         display(x, xptr);
   (gdb) print x
   $5 = 5                                  &lt;--- we have access to variables in frame 1
   (gdb) print xptr
   $6 = (int *) 0xbffffb34                 &lt;--- we have access to variables in frame 1
   (gdb) print z
   No symbol "z" in current context.       &lt;--- we don't have access to variables in frame 0
   (gdb) print zptr
   No symbol "zptr" in current context.    &lt;--- we don't have access to variables in frame 0
</pre>

<p>By the way, one of the hardest things to get used to with GDB is seeing the program's output:</p>

<pre class="demo">
   x is 5 and is stored at 0xbffffb34.
   xptr holds 0xbffffb34 and points to 5.
</pre>

<p>intermixed with GDB's output:</p>

<pre class="demo">
   Starting program: try1
   In main():
   ...
      Breakpoint 1, main () at try1.c:10
   10         display(x, xptr);
</pre>

<p>intermixed with your input to GDB:</p>

<pre class="demo">
   (gdb) run
</pre>

<p>intermixed with your input to the program (which would've been present had we called some kind of input function).  This
can get confusing, but the more you use GDB, the more you get used to it.  Things get tricky when the program does terminal
handling (e.g. ncurses or svga libraries), but there are always ways around it.</p>



<h4 class="exercise">Exercises</h4><ol class="exercise"><li>Continuing from the previous example, switch back to <tt>display()</tt>'s frame.  Verify that you have access to automatic
	variables in <tt>display()</tt>'s frame, but not <tt>main()</tt>'s frame.</li>
<li>Figure out how to quit GDB on your own.  Control-d works, but I want you to guess the command that quits GDB.</li>
<li>GDB has a help feature.  If you type <tt>help foo</tt>, GDB will print a description of command foo.  Enter GDB (don't
	give GDB any arguments) and read the help blurb for all GDB commands we've used in this section.</li>
<li>Debug try1 again and set a breakpoint anywhere in <tt>display()</tt>, then run the program.  Figure out how to display the
	stack along with the values of every local variable for each frame at the same time.  Hint: If you did the previous
	exercise, and read each blurb, this should be easy.</li>
</ol>




<p>&nbsp;</p><br /><hr /><table width="100%"><tr><td width="33%" align="left"><img src="http://www.dirac.org/linux/gdb/inclusions/icons/prev.png" alt="back"/> &nbsp; Back: <a href="01-Introduction.php">Introduction</a></td><td align="left"><img src="http://www.dirac.org/linux/gdb/inclusions/icons/up.png" alt="up" />   &nbsp;<a href="http://www.dirac.org/linux/gdb">Up</a> to the TOC</td><td align="right">Next: <a href="02b-Debugging_With_Your_Brain.php">Debugging With Your Brain</a> &nbsp; <img src="http://www.dirac.org/linux/gdb/inclusions/icons/next.png" alt="next" /></td></tr><tr><td></td><td align="left"><img src="http://www.dirac.org/linux/gdb/inclusions/icons/email.png" alt="email" /> &nbsp;<a href="mailto:p@dirac.org">Email</a> comments and corrections<br /></td></tr><tr><td></td><td align="left"><img src="http://www.dirac.org/linux/gdb/inclusions/icons/printable.png" alt="printable" /> &nbsp;<a href="02a-Memory_Layout_And_The_Stack.php?printable=yes">Printable</a> version</td><td></td></tr></table>
	<br />
	</body>
	</html>