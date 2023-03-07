<?php

namespace Michelf;

class Bootstrap extends MarkdownExtra
{
    # Change too false to remove Markdown from posts and/or comments.
    const WP_POSTS = true;
    const WP_COMMENTS = true;

    # Change to ">" for HTML output
    const EMPTY_ELEMENT_SUFFIX = " />";

    # Define the width of a tab for code blocks.
    const TAB_WIDTH = 4;

    # Optional title attribute for footnote links and backlinks.
    const FN_LINK_TITLE = "";
    const FN_BACKLINK_TITLE = "";

    # Optional class attribute for footnote links and backlinks.
    const FN_LINK_CLASS = "";
    const FN_BACKLINK_CLASS = "";

    # Optional class prefix for fenced code block.
    const CODE_CLASS_PREFIX = "";

    # Class attribute for code blocks goes on the `code` tag;
    # setting this to true will put attributes on the `pre` tag instead.
    const CODE_ATTR_ON_PRE = "";

    public array $moj_placeholders = [];
    public array $moj_hidden_tags = [];

    public function __construct()
    {
        parent::__construct();

        $this->settings();
        $this->filters();

        if (self::WP_COMMENTS) {
            /**
             * Scramble important tags before passing them to the kses filter.
             * We use 10 random strings and 10 important tags.
             */
            $rot = 'pEj07ZbbBZ U1kqgh4w4p pre2zmeN6K QTi31t9pre ol0MP1jzJR ' .
                'ML5IjmbRol ulANi1NsGY J7zRLJqPul liA8ctl16T K9nhooUHli';

            $this->moj_hidden_tags = explode(' ', '<p> </p> <pre> </pre> <ol> </ol> <ul> </ul> <li> </li>');
            $this->moj_placeholders = explode(' ', str_rot13($rot));
        }
    }

    /**
     * Modify Markdown and Markdown Extra
     */
    public function settings()
    {
        $this->empty_element_suffix = self::EMPTY_ELEMENT_SUFFIX;
        $this->tab_width = self::TAB_WIDTH;

        // footnotes
        $this->fn_link_title = self::FN_LINK_TITLE;
        $this->fn_backlink_title = self::FN_BACKLINK_TITLE;
        $this->fn_link_class = self::FN_LINK_CLASS;
        $this->fn_backlink_class = self::FN_BACKLINK_CLASS;

        // code tag
        $this->code_class_prefix = self::CODE_CLASS_PREFIX;
        $this->code_attr_on_pre = self::CODE_ATTR_ON_PRE;
    }

    /**
     * Configures WordPress filters with markdown callbacks
     *
     * Post content and excerpts
     * - Remove WordPress paragraph generator.
     * - Run Markdown on excerpt, then remove all tags.
     * - Add paragraph tag around the excerpt, but remove it for the excerpt rss.
     *
     * Comments
     * - Remove WordPress paragraph generator.
     * - Remove WordPress auto-link generator.
     * - Run Markdown on excerpt then remove paragraph tags.
     */
    public function filters()
    {
        if (self::WP_POSTS) {
            remove_filter('the_content', 'wpautop');
            remove_filter('the_content_rss', 'wpautop');
            remove_filter('the_excerpt', 'wpautop');
            add_filter('the_content', [$this, 'markdownPost'], 6);
            add_filter('the_content_rss', [$this, 'markdownPost'], 6);
            add_filter('get_the_excerpt', [$this, 'markdownPost'], 6);
            add_filter('get_the_excerpt', 'trim', 7);
            add_filter('the_excerpt', [$this, 'addParagraph']);
            add_filter('the_excerpt_rss', [$this, 'stripParagraph']);

            remove_filter('content_save_pre', 'balanceTags', 50);
            remove_filter('excerpt_save_pre', 'balanceTags', 50);
            add_filter('the_content', 'balanceTags', 50);
            add_filter('get_the_excerpt', 'balanceTags', 9);
        }

        if (self::WP_COMMENTS) {
            remove_filter('comment_text', 'wpautop', 30);
            remove_filter('comment_text', 'make_clickable');
            add_filter('pre_comment_content', [$this, 'markdown'], 6);
            add_filter('pre_comment_content', [$this, 'hideTags'], 8);
            add_filter('pre_comment_content', [$this, 'showTags'], 12);
            add_filter('get_comment_text', [$this, 'markdown'], 6);
            add_filter('get_comment_excerpt', [$this, 'markdown'], 6);
            add_filter('get_comment_excerpt', [$this, 'stripParagraph'], 7);
        }

        // Everything else...
        add_filter('the_content', [$this, 'markdown'], 10);
        add_filter('the_excerpt', [$this, 'markdown'], 10);
        add_filter('acf_the_content', [$this, 'markdown'], 10);
    }

    /**
     * Standard Function Interface
     * Transform text
     * @param $text
     * @return string
     */
    public function markdown($text): string
    {
        return $this->transform($text);
    }

    /**
     * Add a footnote id prefix to site posts when inside a loop.
     * @param $text
     * @return string
     */
    public function markdownPost($text): string
    {
        $this->fn_id_prefix = "";

        if (!is_single() || !is_page() || !is_feed()) {
            $this->fn_id_prefix = get_the_ID() . ".";
        }

        return $this->transform($text);
    }

    /**
     * Add paragraphs to given text
     * @param $text
     * @return string
     */
    public function addParagraph($text): string
    {
        if (!preg_match('{^$|^<(p|ul|ol|dl|pre|blockquote)>}i', (string)$text)) {
            $text = '<p>' . $text . '</p>';
            $text = preg_replace('{\n{2,}}', "</p>\n\n<p>", (string)$text);
        }
        return $text;
    }

    /**
     * Removes paragraph HTML tags from text.
     * @param $text
     * @return string
     */
    public function stripParagraph($text): string
    {
        return preg_replace('{</?p>}i', '', (string)$text);
    }

    /**
     * String replace using arrays.
     * Finds specified tags and replaces with scrambled text
     * @param $text
     * @return string
     */
    public function hideTags($text): string
    {
        return str_replace($this->moj_hidden_tags, $this->moj_placeholders, (string)$text);
    }

    /**
     * String replace using arrays.
     * Finds scrambled text and replaces with matching tags
     * @param $text
     * @return string
     */
    public function showTags($text): string
    {
        return str_replace($this->moj_placeholders, $this->moj_hidden_tags, (string)$text);
    }
}
