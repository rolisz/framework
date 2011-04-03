<article>
	<header id="articleHead">
		<a href="/{{ category }}" id="category" class="{{ category }}">{{ category }}</a>
		<h2><a href="{{ slug }}">{{ title }}</a></h2>
		<div class="postInfo">
			<time datetime="{{ date }}" pubdate="pubdate"><a href='#'>{{ date }}</a></time>
			<span>Written by <a href="#">{{ author }}</a></span>
		</div>
		<img src="{{ imageSrc }}" />
	</header>
	<div id="actualText">
		{{ body }}
	</div>
	<footer>
	<div id="tags">
		<span>Tags:</span>
		<ul>
			{foreach $tags as $tag}
				<li><a href="#">{{ tag }}</a></li>
			{/foreach}
		</ul>
	</div>
	</footer>
	<div id="comments">
		<h3>Comments(<?php count($comments); ?>)</h3>
		{if="is_array($comments)"}
		<ul id="commentList">
			{foreach $comments as $comment}
			<li>
				<article>
					<header>
						<div class="commentLeft">
							<img src='{{ comment['image'] }}' />
							<ul> 
								<li>{{ comment['author'] }}</li>
								<li><time datetime="{{ comment['date'] }}" pubdate="pubdate">{{echo comment['date'] }}</time></li>
							</ul>
						</div>
						<div class="commentAction"> 
							<ul>
								<li><a href="reply">Reply</a></li>
								<li><a href="edit">Edit</a></li>
							</ul>
						</div>
					</header>
					<div class="commentContent">
						{{ comment['body'] }}
					</div>
				</article>
			</li>
			{/foreach}
		</ul>
		{/if}
		<div id="commentRespond">
			<h3>Reply</h3>
			<textarea>Comment</textarea>
			<button type="submit">Send reply</button>
		</div>
	</div>
</article>