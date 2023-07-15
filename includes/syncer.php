<?php
namespace WPS3\S3\Offload;

use BWPS\SSU\Aws3\Aws\S3\Exception\S3Exception;
use BWPS\SSU\Aws3\Aws\S3\S3Client;

class Syncer {
    /**
     * S3Client
     *
     * @var S3Client
     */
    private $s3;

    private $options;

    public function __construct() {
        $this->options = $this->getOptions();

        require_once(WPS3_PLUGIN_BASE_DIR . 'lib' . DIRECTORY_SEPARATOR . 'aws3' . DIRECTORY_SEPARATOR . 'aws-autoloader.php');

        $this->s3 = new S3Client( [
            'version'          => 'latest',
            'region'           => $this->options['region'],
            'bucket' 		   => $this->options['bucket'],
            'endpoint' 		   => $this->options['endpoint'],
            'use_path_style_endpoint' => !empty($this->options['pathstyle']),
            
            'credentials'      => [
                'key'    => $this->options['aws_key'],
                'secret' => $this->options['aws_secret'],
            ],
            'signatureVersion' => 'v4',
        ] );

    }

    public function upload_dir($dir) {
        $folder = trim($this->options['folder'], '/');
        if(!empty($folder)) $folder .= '/';

        $dir['path'] = 's3://' . $this->options['bucket'] . '/' . $folder . $dir['subdir'];
        $dir['url'] = trim($this->options['url_prefix'], ' /') . '/' . $folder . trim($dir['subdir'], '/ ');

        $dir['basedir'] = 's3://' . $this->options['bucket'] . '/' . $folder;
        $dir['baseurl'] = trim($this->options['url_prefix'], ' /') . '/' . $folder;

        return $dir;
    }
    
    private function getOptions() {

        return array(
          
            'type'       => defined('WPS3_PROVIDER') ? WPS3_PROVIDER : 'custom',
            'aws_key'    => WPS3_KEY,
            'aws_secret' => WPS3_SECRET,
            'bucket'     => WPS3_BUCKET,
            'region'     => WPS3_REGION,
            'folder'     => defined('WPS3_FOLDER') ? WPS3_FOLDER : '',
            
            'url_prefix' => WPS3_URL_PREFIX,
    
            'endpoint'	 => defined('WPS3_ENDPOINT') ? WPS3_ENDPOINT : '',
            'pathstyle'	 => defined('WPS3_PATHSTYLE') ? WPS3_PATHSTYLE == true : false,
    
        );
    
    }


    public function registerStreamWrapper( ) {
        $this->s3->registerStreamWrapper();
    }
        
    public function delete_attachment($key) {
        
        try {
            $this->s3->deleteObject( [
                'Bucket' => $this->options['bucket'],
                'Key'    => $key,
            ] );
        } catch (S3Exception $exp) {
            throw $exp;
        }

    }

    public function sync($attachment_id) {
        $folder = trim($this->options['folder'], '/');
        if(!empty($folder)) $folder .= '/';

        $metadata = wp_get_attachment_metadata($attachment_id);

        $updated = false;

        if(!isset($metadata['s3'])) {
            if(!empty($metadata['file'])) {
                $filename = $metadata['file'];
            } else {
                $filename = get_post_meta($attachment_id, '_wp_attached_file', true);
            }

            if(is_file(WP_CONTENT_DIR . '/uploads/' . $filename) === false) {
                return;
            }
            
            $params = [
                'Bucket' => $this->options['bucket'], 
                'Key' => $folder . $filename, 
                'Body' => file_get_contents(WP_CONTENT_DIR . '/uploads/' . $filename),
            ];

            try {
                $this->s3->PutObject( $params );
            } catch ( S3Exception $exception ) {
                throw $exception;
            }

            $type = wp_check_filetype_and_ext(WP_CONTENT_DIR . '/uploads/' . $filename, basename($filename));

            update_post_meta($attachment_id, 's3_public_url', $metadata['s3_public_url'] = trim($this->options['url_prefix'], ' /') . '/' . $folder . $filename);

            $metadata['s3'] = [
                'url' => trim($this->options['url_prefix'], ' /') . '/' . $filename,
                'key' => $filename,
                'mime' => $type,
            ];

            $updated = true;
        }

        echo '<pre>';var_dump($metadata);
        if(!empty($metadata['sizes'])) {
            if(empty($filename)) {
                if(!empty($metadata['file'])) {
                    $filename = $metadata['file'];
                } else {
                    $filename = get_post_meta($attachment_id, '_wp_attached_file', true);
                }
            }
            
            foreach($metadata['sizes'] as $sizeKey => $sizeData) {
                $dirname = dirname($filename);

                if(!isset($sizeData['s3'])) {
                    try {

                        $params = [
                            'Bucket' => $this->options['bucket'], 
                            'Key' => $folder . $dirname . DIRECTORY_SEPARATOR . $sizeData['file'], 
                            'Body' => file_get_contents(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $dirname . DIRECTORY_SEPARATOR . $sizeData['file']),
                        ];
            
                        try {
                            $this->s3->PutObject( $params );
                        } catch ( S3Exception $exception ) {
                            throw $exception;
                        }                        

                    } catch (\Exception $exp) {
                        throw $exp;
                    }
        
                    $metadata['sizes'][$sizeKey]['s3'] = [
                        'url' => trim($this->options['url_prefix'], ' /') . $folder . '/' . $dirname . '/' . $sizeData['file'],
                        'key' => $dirname . DIRECTORY_SEPARATOR . $sizeData['file'],
                    ];
                    
                    $updated = true;
                }
            }
        }

        if($updated) {
            wp_update_attachment_metadata( $attachment_id, $metadata );
        }

    }
    
}