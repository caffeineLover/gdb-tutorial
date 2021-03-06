<!-- !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!-->
<!-- Chapter 2b (interlude): Debugging With Your Brain                                           -->
<!-- Wed Nov 22 17:47:26 EST 2006                                                                -->
<!-- 2006 rewrite is done.                                                                       -->
<!-- !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!-->





	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html lang="en">
	<head>
	<title>Peter's gdb Tutorial: Debugging With Your Brain </title>
	<link rel=STYLESHEET type="text/css" href="./inclusions/style.css" title="Style" />
	</head>
	<body>

	<img src="inclusions/images/gnu-head.png" align="right" alt="" /><h1>Using GNU's GDB Debugger</h1><h1>Debugging With Your Brain</h1><h4>By Peter Jay Salzman</h4><hr /><table width="100%"><tr><td align="left">Previous: <a href="02a-Memory_Layout_And_The_Stack.php">Memory Layout And The Stack</a></td><td align="right">Next: <a href="03-Initialization,_Listing,_And_Running.php">Initialization, Listing, And Running</a></td></tr></table><hr />




<br /><br /><a name="pleasereadbeforecontinuing"><h2>Please Read Before Continuing</h2></a>

<p>As of SDL 1.2.11, it appears that <tt>SDL_SetVideoMode()</tt> no longer generates <tt>SIGFPE</tt>
when passed <tt>SDL_OPENGL</tt>.  This means you <i>can</i> use GDB to debug <b>spinning_cube</b>.
However, this is still an <i>excellent</i> example of:</p>
<ol>
<li>How to debug with your brain.</li>
<li>Why knowing theory, like the memory layout of a program, can be helpful when debugging.</li>
</ol>





<br /><br /><a name="debuggingwithyourbrain"><h2>Debugging With Your Brain</h2></a>

<p>In the last section we looked at how a program is laid out in memory.  Knowing this is not only
useful for debugging with GDB, but it's also useful for debugging <i>without</i> GDB.  In this
interlude, guest written by my close friend, <a href="http://cbreak.org">Mark Kim</a>, we'll see how.</p>

<p>Compile and run <a href="./code/02/spinning_cube.tar.bz2">spinning_cube.tar.bz2</a>.  A spinning cube is displayed with images
of Geordi (white) and Juliette (calico), me on a New York City subway, and where I work.</p>

<p>However, when you press a key, some of the cube's textures mysteriously vanish.  My first
instinct was to use GDB to find the problem, but I discovered that SDL programs that use OpenGL
can't be debugged via GDB.  Upon investigation, I found that when you pass the flag
<tt>SDL_OPENGL</tt> to the function <tt>SDL_SetVideoMode()</tt>, a <tt>SIGFPE</tt> is generated
which terminates the program.  If you try to handle the <tt>SIGFPE</tt>, you'll find that
<tt>SDL_SetVideoMode()</tt> never returns, so GDB is left in a hung state.</p>

<p>I had just spent over 40 hours programming over the last 3 days and was getting punch-drunk.  Not
having GDB available pushed me over the edge and I sent an exasperated email to Mark for help.  I
got a reply within 10 minutes.</p>

<p>Before continuing you'll want to:</p>

<ol>
<li>Run the program to see the bug in action.  You need OpenGL and SDL to compile the program.</li>
<li>Look at <tt>HandleKeyPress()</tt> in <b>input.c</b>, which handles keystrokes.
<li>Look at <tt>Debug()</tt>, in <b>yerror.h</b>, which is called from <tt>HandleKeyPress()</tt>.
</ol>

<p>Spend 10 minutes trying to fix the bug.  This will make Mark's email all the more impressive.  As
you read Mark's email, pay particular attention to steps 6, 7B, and 7C for <i>particular</i>
examples of sheer debugging brilliance!</p>

<br />



<pre class="demo">
Hey Peter,

The problem was there was an overlapping memory area between the debugging
variables and the texture variabes.  In video.[hc], the "texture[2]" array
should have been declared "texture[NUM_TEXURES]" instead.  Attached is a
patch file.

The debugging process went like this:

   1. Try Debug() -- indeed it makes some textures disappear.

   2. Try debug_for_reals() into an empty function -- same happens,
      so that's not the problem.

   3. Try removing each line of Debug() macro.  This revealed that
      writing values into the "die_*" variables cause the texture
      to disappear.

   4. So instead of calling Debug(), try writing some values into
      the "die_*" variables -- the textures disappear again.

   5. Check if any other code is using those variables by changing
      variable names and looking out for compilation errors --
      nothing significant showed up.

   6. Perhaps someone is using the same memory space as the "die_*"
      variables unintentionally.  I tried shifting the memory locations of
      the "die_*" variables down by putting an array in front of them,
      like this:

       yerror.c:

         ...
         #include "yerror.h"

       + char buffer[1024];

         // Global Debugging/Dying Variables
         const char *die_filename;
         const char *die_function;
         int        die_line;
         bool       debug = true;

      which fixed the problem.  So now it's a matter of finding the
      overlapping memory.

   7. Tracking down the problem needs some narrowing down of the
      possiblilities, so I made the following assumptions:

      A. I know a problem like this occurs most often when an array
         size is declared too short at another place, so there's probably
         an array out there that's declared too short, and the "die_*"
         variables, placed in memory right after that array, is probably
         getting overwritten by some code expecting the array to be
         longer.

         It could also be a pointer combined with malloc() but at this
         point I'm just thinking about one problem at a time.

      B. The problem must be with either a global or static variable
         since it's overlapping with another global variable
         in the heap space.  So I'm looking for an array declared in
         global or static scope.  That narrows down my search quite a bit.

         BTW, the fact that I'm looking for a variable that overlaps with
         a global variable probably discounts malloc() from our potential
         list of problems since malloc(), if the way I view the memory is
         correct, should allocate memory only *after* all global
         variables, and it's unlikely code accidentally writes to
         a memory location before a pointer rather than an after
         (though it's certainly possible to write to memory before
         a pointer.)  But again, this is all an afterthought... I'm just
         thinking about another global array at this point.

      C. I know the global array I'm looking for must be somehow linked
         to a texture operation since that's what's being interfered by
         writing to the "die_*" variables.  So I'm looking for a global
         array that does something with textures, probably one that stores
         textures or pointers to textures or index to textures or
         something like that.

   8. And that's what I looked for.  texture[2] looked a little suspicious
      so I tried expanding its size and that fixed the problem.  Just to
      make sure, I looked for the code that writes to texture with index
      greater than 1 and found init.c:127 and several places in render.c.

Hope that helps!

-Mark
</pre>


<p>&nbsp;</p><br /><hr /><table width="100%"><tr><td width="33%" align="left"><img src="http://www.dirac.org/linux/gdb/inclusions/icons/prev.png" alt="back"/> &nbsp; Back: <a href="02a-Memory_Layout_And_The_Stack.php">Memory Layout And The Stack</a></td><td align="left"><img src="http://www.dirac.org/linux/gdb/inclusions/icons/up.png" alt="up" />   &nbsp;<a href="http://www.dirac.org/linux/gdb">Up</a> to the TOC</td><td align="right">Next: <a href="03-Initialization,_Listing,_And_Running.php">Initialization, Listing, And Running</a> &nbsp; <img src="http://www.dirac.org/linux/gdb/inclusions/icons/next.png" alt="next" /></td></tr><tr><td></td><td align="left"><img src="http://www.dirac.org/linux/gdb/inclusions/icons/email.png" alt="email" /> &nbsp;<a href="mailto:p@dirac.org">Email</a> comments and corrections<br /></td></tr><tr><td></td><td align="left"><img src="http://www.dirac.org/linux/gdb/inclusions/icons/printable.png" alt="printable" /> &nbsp;<a href="02b-Debugging_With_Your_Brain.php?printable=yes">Printable</a> version</td><td></td></tr></table>
	<br />
	</body>
	</html>