<hr class="kss-divider">
<div class="kss-social-strip kss-horizontal">

    <?php if ($data['pdf_url']): ?>
        <div class="kss-action">
            <a href="<?= esc_url($data['pdf_url']); ?>" class="kss-icon" download title="Download (1 credit)">
                <img src="<?= esc_url($data['icon_base'] . 'download.png'); ?>" alt="Download">
            </a>
            <span class="kss-label">Download PDF (with Credit)</span>
        </div>
    <?php endif; ?>

    <div class="kss-action">
        <button class="kss-save-button" data-post-id="<?= esc_attr($data['post_id']); ?>" title="Save to Library">
            <img src="<?= esc_url($data['icon_base'] . 'bookmark.png'); ?>" alt="Save to Library">
        </button>
        <span class="kss-label">Save to Online Library</span>
    </div>

    <?php if ($data['price'] > 0): ?>
        <div class="kss-action">
            <button class="kss-buy-button" title="Buy (£<?= number_format($data['price'], 2); ?>)">
                <img src="<?= esc_url($data['icon_base'] . 'buy.png'); ?>" alt="Buy PDF">
            </button>
            <span class="kss-label">Buy PDF (Without Credits)</span>
        </div>
        <div class="kss-action">
            <button class="kss-buy-button" title="Send as Gift (£<?= number_format($data['price'], 2); ?>)">
                <img src="<?= esc_url($data['icon_base'] . 'gift.png'); ?>" alt="Gift Article">
            </button>
            <span class="kss-label">Send Article as a Gift</span>
        </div>
    <?php endif; ?>

    <div class="kss-action">
        <button class="ssm-share-trigger" data-title="<?= esc_attr(get_the_title()); ?>" data-url="<?= esc_url(get_permalink()); ?>" title="Share">
            <img src="<?= esc_url($data['icon_base'] . 'share.png'); ?>" alt="Share Article">
        </button>
        <span class="kss-label">Share via Email & Socials</span>
    </div>

</div>
