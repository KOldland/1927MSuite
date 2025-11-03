import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save({ attributes }) {
	const { url, alt, caption, enableLightbox } = attributes;
	const blockProps = useBlockProps.save({
		className: enableLightbox ? 'lightbox' : undefined,
		'data-enable-lightbox': enableLightbox ? 'true' : undefined,
	});

	if (!url) {
		return null;
	}

	return (
		<figure {...blockProps}>
		<a href={url} 
		data-lightbox="image-lightbox"
		rel="lightbox"
		>
		<img src={url} alt={alt} />
		</a>
		{caption && <RichText.Content tagName="figcaption" value={caption} />}
		</figure>
	);
}
