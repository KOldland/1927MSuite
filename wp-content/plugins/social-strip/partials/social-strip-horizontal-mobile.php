<div class="kss-social-strip kss-horizontal kss-horizontal-mobile">
    <?php if ($data['pdf_url']): ?>
        <a href="<?= esc_url($data['pdf_url']); ?>" class="kss-icon" download title="Download (1 credit)">
            <img src="<?= esc_url($data['icon_base'] . 'download.png'); ?>" alt="Download">
        </a>
    <?php endif; ?>

    <button class="kss-save-button" data-post-id="<?= esc_attr($data['post_id']); ?>" title="Save to Library">
        <img src="<?= esc_url($data['icon_base'] . 'bookmark.png'); ?>" alt="Save">
    </button>

    <?php if ($data['price'] > 0): ?>
        <button class="kss-buy-button" title="Buy (£<?= number_format($data['price'], 2); ?>)">
            <img src="<?= esc_url($data['icon_base'] . 'buy.png'); ?>" alt="Buy PDF">
        </button>
        <button class="kss-buy-button" title="Send as Gift (£<?= number_format($data['price'], 2); ?>)">
            <img src="<?= esc_url($data['icon_base'] . 'gift.png'); ?>" alt="Gift Article">
        </button>
    <?php endif; ?>

    <button class="ssm-share-trigger" data-title="<?= esc_attr(get_the_title()); ?>" data-url="<?= esc_url(get_permalink()); ?>" title="Share">
        <img src="<?= esc_url($data['icon_base'] . 'share.png'); ?>" alt="Share Article">
    </button>
</div>
