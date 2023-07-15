<?php
namespace WPS3\S3\Offload;

class Frontend {
    public function load_hooks() {

        add_filter( 'wp_get_attachment_url', [$this, 'change_file_url_in_media'], 10, 2 );
    }

	private function is_upload_to_s3( $post_id ) {
		$s3_public_url = get_post_meta( $post_id, 's3_public_url', true );
        
		if ( empty( $s3_public_url ) ) {
			return false;
		}

		return true;
	}

	public function change_file_url_in_media( $url, $attachment_id ) {
		if ( ! $this->is_upload_to_s3( $attachment_id ) ) {
			return $url;
		}

		return get_post_meta( $attachment_id, 's3_public_url', true );
	}     

}