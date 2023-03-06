<?php
#
# Markdown Extra - A text-to-HTML conversion tool for web writers
#
# PHP Markdown & Extra  
# Copyright (c) 2004-2013 Michel Fortin  
# <http://michelf.ca/projects/php-markdown/>
#
# Original Markdown  
# Copyright (c) 2004-2006 John Gruber  
# <http://daringfireball.net/projects/markdown/>
#

require MOJ_ROOT_DIR . '/vendor/autoload.php';

use Michelf\MarkdownExtra;

const MARKDOWN_VERSION = "1.0.2";
const MARKDOWN_EXTRA_VERSION = "1.2.8";

# Change to ">" for HTML output
const MARKDOWN_EMPTY_ELEMENT_SUFFIX = " />";

# Define the width of a tab for code blocks.
const MARKDOWN_TAB_WIDTH = 4;

# Optional title attribute for footnote links and backlinks.
const MARKDOWN_FN_LINK_TITLE = "";
const MARKDOWN_FN_BACKLINK_TITLE = "";

# Optional class attribute for footnote links and backlinks.
const MARKDOWN_FN_LINK_CLASS = "";
const MARKDOWN_FN_BACKLINK_CLASS = "";

# Optional class prefix for fenced code block.
const MARKDOWN_CODE_CLASS_PREFIX = "";

# Class attribute for code blocks goes on the `code` tag;
# setting this to true will put attributes on the `pre` tag instead.
const MARKDOWN_CODE_ATTR_ON_PRE = "";

#
# WordPress settings:
#

# Change too false to remove Markdown from posts and/or comments.
const MARKDOWN_WP_POSTS = true;
const MARKDOWN_WP_COMMENTS = true;

### Standard Function Interface ###
function moj_markdown($text): string
{
    # Setup static parser variable.
    static $parser;
    if (!isset($parser)) {
        $parser = new MarkdownExtra;
    }

    # Transform text using parser.
    return $parser->transform($text);
}

# Add a footnote id prefix to posts when inside a loop.
function moj_markdown_post($text): string
{
    static $parser;
    if (!$parser) {
        $parser = new MarkdownExtra;
    }

    $parser->fn_id_prefix = "";

    if (!is_single() || !is_page() || !is_feed()) {
        $parser->fn_id_prefix = get_the_ID() . ".";
    }

    return $parser->transform($text);
}


if (isset($wp_version)) {

    # Post content and excerpts
    # - Remove WordPress paragraph generator.
    # - Run Markdown on excerpt, then remove all tags.
    # - Add paragraph tag around the excerpt, but remove it for the excerpt rss.
    if (MARKDOWN_WP_POSTS) {
        remove_filter('the_content', 'wpautop');
        remove_filter('the_content_rss', 'wpautop');
        remove_filter('the_excerpt', 'wpautop');
        add_filter('the_content', 'moj_markdown_post', 6);
        add_filter('the_content_rss', 'moj_markdown_post', 6);
        add_filter('get_the_excerpt', 'moj_markdown_post', 6);
        add_filter('get_the_excerpt', 'trim', 7);
        add_filter('the_excerpt', 'moj_add_p');
        add_filter('the_excerpt_rss', 'moj_strip_p');

        remove_filter('content_save_pre', 'balanceTags', 50);
        remove_filter('excerpt_save_pre', 'balanceTags', 50);
        add_filter('the_content', 'balanceTags', 50);
        add_filter('get_the_excerpt', 'balanceTags', 9);
    }

    # Comments
    # - Remove WordPress paragraph generator.
    # - Remove WordPress auto-link generator.
    # - Scramble important tags before passing them to the kses filter.
    # - Run Markdown on excerpt then remove paragraph tags.
    if (MARKDOWN_WP_COMMENTS) {
        remove_filter('comment_text', 'wpautop', 30);
        remove_filter('comment_text', 'make_clickable');
        add_filter('pre_comment_content', 'moj_markdown', 6);
        add_filter('pre_comment_content', 'moj_hide_tags', 8);
        add_filter('pre_comment_content', 'moj_show_tags', 12);
        add_filter('get_comment_text', 'moj_markdown', 6);
        add_filter('get_comment_excerpt', 'moj_markdown', 6);
        add_filter('get_comment_excerpt', 'moj_strip_p', 7);

        global $moj_hidden_tags, $moj_placeholders;
        $rot = 'pEj07ZbbBZ U1kqgh4w4p pre2zmeN6K QTi31t9pre ol0MP1jzJR ML5IjmbRol ulANi1NsGY J7zRLJqPul liA8ctl16T K9nhooUHli';
        $moj_hidden_tags = explode(' ', '<p> </p> <pre> </pre> <ol> </ol> <ul> </ul> <li> </li>');
        $moj_placeholders = explode(' ', str_rot13($rot));
    }

    function moj_add_p($text): string
    {
        if (!preg_match('{^$|^<(p|ul|ol|dl|pre|blockquote)>}i', (string)$text)) {
            $text = '<p>' . $text . '</p>';
            $text = preg_replace('{\n{2,}}', "</p>\n\n<p>", (string)$text);
        }
        return $text;
    }

    function moj_strip_p($text): string
    {
        return preg_replace('{</?p>}i', '', (string)$text);
    }

    function moj_hide_tags($text): string
    {
        global $moj_hidden_tags, $moj_placeholders;
        return str_replace($moj_hidden_tags, $moj_placeholders, (string)$text);
    }

    function moj_show_tags($text): string
    {
        global $moj_hidden_tags, $moj_placeholders;
        return str_replace($moj_placeholders, $moj_hidden_tags, (string)$text);
    }
}
