( function ( wp ) {
	if ( ! wp || ! wp.plugins || ! wp.element ) {
		return;
	}

	const { registerPlugin } = wp.plugins;
	const editPost = wp.editPost || {};
	const editor = wp.editor || {};
	const PluginDocumentSettingPanel = editPost.PluginDocumentSettingPanel;
	const PluginSidebar = editPost.PluginSidebar || editor.PluginSidebar;
	const PanelComponent = PluginDocumentSettingPanel || PluginSidebar;

	if ( ! PanelComponent ) {
		return;
	}

	const { Button } = wp.components;
	const { useSelect } = wp.data;
	const { useState, createElement: el } = wp.element;
	const { __ } = wp.i18n;

	const config = window.ezcpsBlockEditor || {};
	const allowedPostTypes = config.postTypes || [];

	function PushContentPanel() {
		const postId = useSelect(
			( select ) => select( 'core/editor' ).getCurrentPostId(),
			[]
		);

		const postType = useSelect(
			( select ) => select( 'core/editor' ).getCurrentPostType(),
			[]
		);

		const [ message, setMessage ] = useState( '' );
		const [ isError, setIsError ] = useState( false );
		const [ isLoading, setIsLoading ] = useState( false );

		if ( ! postType || ! allowedPostTypes.includes( postType ) ) {
			return null;
		}

		const handlePush = () => {
			if ( ! postId ) {
				return;
			}

			setIsLoading( true );
			setMessage( __( 'Pushing...', 'easy-content-push' ) );
			setIsError( false );

			const formData = new FormData();
			formData.append( 'action', 'ezcps_push_to_live' );
			formData.append( 'security', config.nonce );
			formData.append( 'post_id', String( postId ) );

			fetch( config.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
			} )
				.then( ( response ) => response.json() )
				.then( ( response ) => {
					setIsLoading( false );
					if ( response.success ) {
						setIsError( false );
						setMessage( response.data.message );
					} else {
						setIsError( true );
						setMessage(
							response.data && response.data.message
								? response.data.message
								: __( 'Something went wrong.', 'easy-content-push' )
						);
					}
				} )
				.catch( ( error ) => {
					setIsLoading( false );
					setIsError( true );
					setMessage(
						__( 'Error:', 'easy-content-push' ) + ' ' + error.message
					);
				} );
		};

		const panelProps = PluginDocumentSettingPanel
			? {
					name: 'ezcps-push-content',
					title: __( 'Easy ContentPush', 'easy-content-push' ),
					className: 'ezcps-document-panel',
			  }
			: {
					name: 'ezcps-push-to-live',
					title: __( 'Easy ContentPush', 'easy-content-push' ),
					icon: 'upload',
					className: 'ezcps-plugin-sidebar',
			  };

		return el(
			PanelComponent,
			panelProps,
			el(
				'div',
				{ className: 'ezcps-editor-panel' },
				el(
					'p',
					{ className: 'ezcps-editor-panel__help' },
					__(
						'Send this post to your configured target site.',
						'easy-content-push'
					)
				),
				el(
					Button,
					{
						variant: 'primary',
						className: 'ezcps-push-button',
						onClick: handlePush,
						isBusy: isLoading,
						disabled: isLoading || ! postId,
					},
					__( 'Push content', 'easy-content-push' )
				),
				message &&
					el(
						'p',
						{
							className:
								'ezcps-push-message ' +
								( isError ? 'is-error' : 'is-success' ),
						},
						message
					)
			)
		);
	}

	registerPlugin( 'ezcps-push-to-live', {
		render: PushContentPanel,
		icon: 'upload',
	} );
} )( window.wp );
