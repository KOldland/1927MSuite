document.addEventListener('DOMContentLoaded', function () {
	(function (blocks, editor, components, element) {
		if (!blocks || !editor || !components || !element) {
			console.warn("💥 Block editor not ready — skipping block registration.");
			return;
		}

		const { registerBlockType } = blocks;
		const { MediaUpload, MediaUploadCheck, InspectorControls, RichText, useBlockProps } = editor;
		const { Button, PanelBody, ToggleControl } = components;
		const el = element.createElement;
		const Fragment = element.Fragment;

		registerBlockType('custom/image-lightbox', {
			title: 'Image (Lightbox)',
			icon: 'format-image',
			category: 'media',
			attributes: {
				url: { type: 'string' },
				alt: { type: 'string' },
				caption: { type: 'string' },
				enableLightbox: { type: 'boolean', default: false }
			},

			edit({ attributes, setAttributes }) {
				const { url, alt, caption, enableLightbox } = attributes;
				const blockProps = useBlockProps();

				return el(
					Fragment,
					null,
					el(InspectorControls, {},
						el(PanelBody, { title: 'Lightbox Settings', initialOpen: true },
							el(ToggleControl, {
								label: 'Enable Lightbox',
								checked: enableLightbox,
								onChange: (val) => setAttributes({ enableLightbox: val })
							})
						)
					),
					el("div", blockProps,
						el(MediaUploadCheck, {},
							el(MediaUpload, {
								onSelect: (media) => setAttributes({ url: media.url, alt: media.alt }),
								allowedTypes: ['image'],
								value: url,
								render: ({ open }) =>
								el(Button, { onClick: open, variant: 'primary' }, url ? 'Replace Image' : 'Upload Image')
							})
						),
						url && el("img", { src: url, alt: alt, style: { maxWidth: '100%' } }),
						url && el(RichText, {
							tagName: "figcaption",
							value: caption,
							onChange: (val) => setAttributes({ caption: val }),
							placeholder: "Write caption…"
						})
					)
				);

			},

			save({ attributes }) {
				const { url, alt, caption, enableLightbox } = attributes;
				const blockProps = useBlockProps.save({
					className: enableLightbox ? 'lightbox' : undefined,
					'data-enable-lightbox': enableLightbox ? 'true' : undefined
				});

				if (!url) return null;

				return el("figure", blockProps,
					el("img", { src: url, alt: alt }),
					caption && el(RichText.Content, {
						tagName: "figcaption",
						value: caption
					})
				);
			}
		});
	})(
		window.wp.blocks,
		window.wp.blockEditor,
		window.wp.components,
		window.wp.element
	);
});

