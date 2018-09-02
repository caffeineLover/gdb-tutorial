<!-- !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! -->
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html lang="en">
	<head>
	<title>Peter's gdb Tutorial: Administrata </title>
	<link rel=stylesheet type="text/css" href="./inclusions/style.css" title="Style" />

	</head>
	<body>

	<img src="inclusions/images/gnu-head.png" align="right" alt="" /><h1>Using GNU's GDB Debugger</h1><h1>Administrata</h1><h4>By Peter Jay Salzman</h4><hr /><table width="100%"><tr><td align="left">Previous: <a href="index.php">Table Of Contents</a></td><td align="right">Next: <a href="01-Introduction.php">Introduction</a></td></tr></table><hr />




<br /><br /><a name="whywritethistutorial"><h2>Why Write This Tutorial?</h2></a>

<p>This is one of the most comprehensive GDB tutorials on the Internet.  It's more than you'd find in most books, which tend
to discuss GDB as an afterthought.  I initially wrote this because I couldn't find a good GDB tutorial.  The only source of
information about GDB is GNU's GDB User's Manual (this was 1997, so a long time ago by now), but learning GDB from it is like
learning a foreign language from a dictionary.</p>

<p>I'll be using sample programs, and there will be links to the source code in each section that uses them, along with
compilation instructions.  I urge you to download the code and follow along with the examples.  Following along, doing it
yourself as you read, is really the second best way to learn.</p>

<p>The best way to learn is by playing.  Go beyond the scope of what I talk about.  Use your knowledge in ways neither of us
anticipated.  Not everyone has the capacity to "play" with knowledge, but if you're one of them, experimenting with your own
ideas and curiosities will take you far.</p>





<br /><br /><a name="acknowledgementsanddedication"><h2>Acknowledgements And Dedication</h2></a>

<p>I'm in a perpetual state of learning, and thanks goes to the following people who've helped me understand C and GDB:</p>

<ul>
<li><b>Will Deutsch:</b> For answering questions about GDB.</li>
<li><b>Mike Simons:</b> For answering questions about GDB.</li>
<li><b>Paul Hinton:</b> of Wolfram Research for convincing me to try this crazy thing called "GNU/Linux".</li>
<li><b>Jeff Newmiller:</b> Who has yet to be stumped by any question I throw at him.</li>
<li><b>Norm Matloff:</b> Who seems to know everything that I don't know (which is a LOT!)</li>
<li><b>Mark K. Kim:</b> Who never tires of my questions and has an amazing ability to incorporate out-of-box thinking
	with formal learning.  A true hacker, good friend, and humble guy.</li>
</ul>







<br /><br /><a name="authorshipandcopyright"><h2>Authorship And Copyright</h2></a>

<p>This entire tutorial is copyright (c) 2004 Peter Jay Salzman, <a href="mailto:p@dirac.org">p@dirac.org</a>.  Permission is granted to copy,
distribute and/or modify it under the terms of The Open Source License, version 3.0.  You can find a copy of this license at
<a href="http://https://opensource.org/licenses/OSL-3.0">https://opensource.org/licenses/OSL-3.0</a></p>

<p>The canonical and most updated version of this document can be found at <a href="http://www.dirac.org/linux/gdb">www.dirac.org/linux/gdb</a>.

<p>If you want to create a derivative work or publish this document for commercial purposes, I would appreciate it if you
contacted me first.  This will give me a chance to give you the most recent version.  It'll also stroke my ego.  I'd also
appreciate either a copy of whatever it is you're doing or a spinach, garlic, mushroom, feta cheese and artichoke heart
pizza.</p>







<br /><br /><a name="aboutexercises"><h2>About Exercises</h2></a>

<p>There are exercises at the end of most sections.  The exercises are <b>mandatory</b>.  These exercises are designed to
both cover topics I don't formally cover, and give you experience using your new-found skills.</p>

<p>There are topics I don't cover except for in the exercises.  This isn't because I'm lazy.  It's because I want you to
think.  Use your noggin to begin understanding concepts in your own words, not in my words.  I want you to develop intuition.
The best debugging tool is <i>not</i> GDB.  And it certainly isn't <tt>printf()</tt>.  The best debugging tool is your
brain.</p>







<br /><br /><a name="thankyous"><h2>Thank Yous</h2></a>

<p>The following people sent in corrections (remove the "ZZZ" in the email address):</p>

<ul>
<li><a href="mailto:placardZZZ@programmer.net">Nick</a></li>
<li>Jason E. Siefken (from Oregon State University?) Can someone please put me in touch with him?</li>
<li><a href="mailto:estuebe_bugchaser@xenruZZZ.org">Eric T. Stuebe</a></li>
<li><a href="mailto:jsterrel@ZZZunc.edu">Jeff Terrell</a></li>
<li><a href="mailto:lrpoormanZZZ@web.de">Lawrence Poorman</a></li>
<li><a href="mailto:larkvmZZZ@gmail.com">Yi Yang</a></li>
<li><a href="mailto:aaron.mayersonZZZ@gmail.com">Aaron Mayerson</a></li>
<li><a href="mailto:doug.yoder@nventure.com">Doug Yoder</a></li> <!-- 29 May 2006 -->
</ul>




<br /><br /><a name="aplugfortheelectronicfrontierfoundation(eff)"><h2>A Plug For The Electronic Frontier Foundation (EFF)</h2></a>

<p>If you're not a member of the <a href="http://www.eff.org">EFF</a>, you must stop everything you're doing and become a member
right this moment.  9/11 was a horrible tragedy; I was in New York City at the time and witnessed the chaos with my own two
eyes.  I love my country, and am a very proud United States citizen, but the steady erosion of our freedoms and civil liberty
is another tragic casualty of the post 9/11 era.  I'm very worried for my country.</p>

<p>The EFF is the most important defense we have in protecting our on-line and digital rights.  If you have any interest in
protecting your civil liberties in a digital age that has gone out of balance, please read their very short <?
a('eff.org/mission.php','mission') ?>.  Please consider becoming a <a href="https://secure.eff.org/">member</a> of the EFF.  Honestly,
it's only the price of a pizza.  Or the cost of two movie tickets plus popcorn.</p>






<br /><br /><a name="arequestforhelp"><h2>A Request For Help</h2></a>

<p>This tutorial took (takes?) more time than I care to admit.  It's a tremendous job.  If you found this tutorial to be at
all useful, please consider helping me maintain and actively develop it.  There are many ways you can help.  Pick one that
suits you or your talents (in no particular order):</p>

<ul>
<li>Become a <a href="https://supporters.eff.org/donate/join-or-renew-your-membership">member of the EFF</a>, or buy their
	<a href="https://supporters.eff.org/shop">merchandise</a>.</li>
<li>Become a <a href="https://my.fsf.org/join">member of the FSF</a>, or buy their <a href="https://shop.fsf.org/collection/gnu-gear">merchandise</a>.</li>
<li>If you're handy with HTML or PHP, please send an email offering to help with the website.  I don't want fancy pages (I
	want lynx/links users to be able to use this site), but I'm really just a "hack" at HTML and PHP.  If you're handy with
	design or formatting, please offer some advice on how to make my pages more readable and good looking.</li>
<li>Report spelling errors, technical errors, and broken links.</li>
<li>Email me questions.  Tell me if something isn't clear.</li>
<li>Send me a picture postcard from where you live.  Better yet, send me a letter with either a picture of yourself or the
	area where you live.  Let me know how you got involved with free software and what you use GDB for.  My mailing address
	is Peter Jay Salzman, 416 68th Street apt 1-B, Brooklyn NY 11220, USA.</li>
<li>Proofread.</li>
<li>If you live in Italy, email me pictures of pizza from your country.  No, this isn't a joke.  I really love pizza.</li>
</ul>

<p>I may not respond to all email.  It really depends on how busy my life is at the moment (this isn't my whole life, you
see).  If I have time, I'll try to reply.  But please don't get upset if I can't reply.  It means I'm swamped with work and
can't afford to reply.  Rest assured, even if you don't hear from me, receiving an email from you is still helpful.  It
reassures me that people are reading and benefiting from my work.</p>






<br /><br /><a name="linkstomygdbpages"><h2>Links to my GDB pages</h2></a>

<p>Some day I'd like to see my GDB tutorial as the first return on a Google search for "GDB tutorial".  There are currently
(Jan 2006) <a href="http://www.google.com/search?hl=en&q=gdb+tutorial&btnG=Google+Search">four returns before my tutorial</a>.  If
you find my tutorial to be at all useful, please link to it and give me some Google-love.  :)  I'll return the favor.  So
far, these GDB pages have been linked to by:</p>

<ol>
<li><?a('www.livejournal.com/users/madhusudan/11556.html?mode=reply','Madhusudan\'s blog') ?></li>
<li>The <a href="http://www.debian.org">Debian GNU/Linux</a> package comes with a file <tt>usr/share/doc/gdb/README.Debian</tt> that
	contains:
<pre>
GDB is a complex program.  It comes with an Info manual (`info gdb' or your
favorite other info browser), which serves as a good command reference.

There are also a number of books and tutorials devoted to GDB.  One
particularly useful guide is Peter Jay Salzman's, at:
  http://www.dirac.org/linux/gdb/
</pre>
I nearly fainted when I saw that, since the person who wrote this is probably <a href="http://www.gnu.org/software/gdb/committee">Daniel Jacobowitz</a>, who is a lead developer of GDB and a member of the GDB steering committee.  Quite an honor!</li>
</ol>

<p>If you link to my pages or use these pages for training material, please let me know so I can put you and a link to your
work up in this section (or simply describe it), which I'm officially calling my
"<a href="./08-Other_Stuff.php#kudos">ego page</a>". &nbsp;<tt>:)</tt></p>


<p>&nbsp;</p><br /><hr /><table width="100%"><tr><td width="33%" align="left"><img src="http://www.dirac.org/linux/gdb/inclusions/icons/prev.png" alt="back"/> &nbsp; Back: <a href="index.php">Table Of Contents</a></td><td align="left"><img src="http://www.dirac.org/linux/gdb/inclusions/icons/up.png" alt="up" />   &nbsp;<a href="http://www.dirac.org/linux/gdb">Up</a> to the TOC</td><td align="right">Next: <a href="01-Introduction.php">Introduction</a> &nbsp; <img src="http://www.dirac.org/linux/gdb/inclusions/icons/next.png" alt="next" /></td></tr><tr><td></td><td align="left"><img src="http://www.dirac.org/linux/gdb/inclusions/icons/email.png" alt="email" /> &nbsp;<a href="mailto:p@dirac.org">Email</a> comments and corrections<br /></td></tr><tr><td></td><td align="left"><img src="http://www.dirac.org/linux/gdb/inclusions/icons/printable.png" alt="printable" /> &nbsp;<a href="00-Administrata.php?printable=yes">Printable</a> version</td><td></td></tr></table>
	<br />
	</body>
	</html>
