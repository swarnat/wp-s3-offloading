<?php
namespace WPS3\S3\Offload;

class Admin {
    public function load_hooks() {

        add_filter( 'media_row_actions', [$this, 'media_row_actions'], 10, 3 );
		
        add_action( 'admin_post_s3_sync', [$this, 'admin_post_s3_sync'], 10, 0 );
		add_action( 'admin_post_check_missed_sync', [$this, 'admin_check_missed_sync'], 10, 0 );

        add_action( 'delete_attachment', [$this, 'handle_delete_post_meta'], 10 );

    }

    public function handle_delete_post_meta( $attachment_id ) {
        $file_url = get_post_meta( $attachment_id, 's3_public_url', true );

        if ( $this->is_upload_to_s3( $attachment_id ) ) {
            $syncerObj = new Syncer();

            $metadata = wp_get_attachment_metadata($attachment_id);

            if(!empty($metadata['s3']['key'])) {
                $syncerObj->delete_attachment($metadata['s3']['key']);
            }

            delete_post_meta( $attachment_id, 's3_public_url' );
            
            if(!empty($metadata['sizes'])) {
                foreach($metadata['sizes'] as $size) {
                    if(!empty($size['s3'])) {
                        $syncerObj->delete_attachment( $size['s3']['key'] );
                    }
                }
            }
        }
    }

	private function is_upload_to_s3( $post_id ) {
		$s3_public_url = get_post_meta( $post_id, 's3_public_url', true );
        
		if ( empty( $s3_public_url ) ) {
			return false;
		}

		return true;
	}

    public function media_row_actions($actions, $post, $detached) {
        $actions['s3sync'] = sprintf(
            '<a href="%s">%s</a>',
            wp_nonce_url( "admin-post.php?action=s3_sync&amp;post=$post->ID", 'sync-s3-' . $post->ID ),
            __( 'Sync Storage' )
        );

        return $actions;
    }

    public function admin_post_s3_sync() {
        $attachment_id = (int)$_REQUEST['post'];

        $syncerObj = new Syncer();
        $syncerObj->sync($attachment_id);
    }

    public function admin_check_missed_sync() {
        
        $attachments = get_posts( array(        
            'post_type' => 'attachment',
            'posts_per_page' => 10,
            'meta_query' => array(
				array(
					'key'     => 's3_public_url',
					'compare' => 'NOT EXISTS'
				)
			)
        ) );
        
        $syncerObj = new Syncer();

        foreach($attachments as $attachment) {
            $syncerObj->sync($attachment->ID);
        }
    }

}