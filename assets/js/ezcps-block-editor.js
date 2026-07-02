( function ( wp ) {
	const { registerPlugin } = wp.plugins;
	const { PluginSidebar } = wp.editor || wp.editPost;
	const { PanelBody, Button } = wp.components;
	const { useSelect } = wp.data;
	const { useState, createElement: el } = wp.element;
	const { __ } = wp.i18n;

	const config = window.ezcpsBlockEditor || {};
	const allowedPostTypes = config.postTypes || [];

	function PushToLivePanel() {
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

		if ( ! allowedPostTypes.includes( postType ) ) {
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

		return el(
			PluginSidebar,
			{
				name: 'ezcps-push-to-live',
				title: __( 'Easy ContentPush', 'easy-content-push' ),
				icon: 'upload',
			},
			el(
				PanelBody,
				{ title: __( 'Push to Live', 'easy-content-push' ) },
				el( Button, {
					variant: 'primary',
					onClick: handlePush,
					isBusy: isLoading,
					disabled: isLoading || ! postId,
				}, __( 'Push to Live', 'easy-content-push' ) ),
				message &&
					el(
						'p',
						{ style: { color: isError ? '#d63638' : '#00a32a' } },
						message
					)
			)
		);
	}

	registerPlugin( 'ezcps-push-to-live', {
		render: PushToLivePanel,
		icon: 'upload',
	} );
} )( window.wp );
