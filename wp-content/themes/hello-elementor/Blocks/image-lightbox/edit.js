import { useBlockProps, MediaUpload, MediaUploadCheck, RichText, InspectorControls } from '@wordpress/block-editor';
import { Button, PanelBody, ToggleControl } from '@wordpress/components';
import { Fragment } from '@wordpress/element';

export default function Edit({ attributes, setAttributes }) {
	const { url, alt, caption, enableLightbox } = attributes;

	const blockProps = useBlockProps();

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody title="Lightbox Settings">
					<ToggleControl
						label="Enable Lightbox"
						checked={enableLightbox}
						onChange={(value) => setAttributes({ enableLightbox: value })}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<MediaUploadCheck>
					<MediaUpload
						onSelect={(media) => setAttributes({ url: media.url, alt: media.alt })}
						allowedTypes={['image']}
						value={url}
						render={({ open }) => (
							<Button onClick={open} variant="primary">
								{url ? 'Replace Image' : 'Upload Image'}
							</Button>
						)}
					/>
				</MediaUploadCheck>

				{url && (
					<>
						<img src={url} alt={alt} style={{ maxWidth: '100%' }} />
						<RichText
							tagName="figcaption"
							value={caption}
							onChange={(value) => setAttributes({ caption: value })}
							placeholder="Write caption…"
						/>
					</>
				)}
			</div>
		</Fragment>
	);
}
