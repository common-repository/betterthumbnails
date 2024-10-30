<?php
/*
Plugin Name: BetterThumbnails
Plugin URI: http://www.laullon.com/betterthumbnails-wordpress-plugin/
Description: Auto-resize your post thumbnails when the config are changed, and fix the thumbnail names.
Version: 1.0.0
Author: German Laullon
Author URI: http://laullon.com/
*/
/*  Copyright YEAR  PLUGIN_AUTHOR_NAME  (email : laullon@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_filter( 'wp_generate_attachment_metadata', 'ftblg_thumbnails_names' );
add_filter( 'image_downsize', 'ftblg_image_downsize' ,10,3);


function ftblg_thumbnails_names($metadata){
    $upload = wp_upload_dir();
    $uploadpath = $upload['basedir'];
    $dir=dirname($uploadpath.'/'.$metadata['file']);

    if(is_array($metadata['sizes'])){
        foreach($metadata['sizes'] as $size => $meta){
            $old_size="{$meta[width]}x{$meta[height]}";
            $new_file=str_replace($old_size, $size, $meta['file']);
            if(file_exists($dir."/".$meta['file'])){
                rename($dir."/".$meta['file'], $dir."/".$new_file);
                $metadata["sizes"][$size]['file']=$new_file;
            }
        }
    }
    return $metadata;
}

function ftblg_image_downsize($pp, $id,$size){
    if(is_array($size)) return false;
    if($size=='full') return;

    $upload = wp_upload_dir();
    $full_file=$img_url = get_post_meta( $id, '_wp_attached_file', true);
    debug("img_url='$img_url'");
    debug("-".strrpos($img_url, $upload[basedir]));
    if(strrpos($img_url, $upload[basedir])===false){
        $full_file = "{$upload[basedir]}/$img_url";
    }
    debug("full_file='$full_file'");

    $meta = wp_get_attachment_metadata($id);
    debug("meta=".var_export($meta, true));
    $rg=true;
    if(is_array($meta)){
        if(is_array($meta['sizes'][$size])){
            extract($meta['sizes'][$size]); // $file

            $crop=get_option("{$size}_crop",0);
            $sz_w=get_option("{$size}_size_w");
            $sz_h=get_option("{$size}_size_h");

            $dir=dirname($full_file);
            $file=$meta['sizes'][$size]['file'];
            $file="$dir/$file";
            if(file_exists($full_file)){
                list($fw,$fh)=getimagesize($full_file);
                list(,,,, $new_w, $new_h) = image_resize_dimensions($fw, $fh, $sz_w, $sz_h, $crop);
                if(file_exists($file)){
                    list($fw,$fh)=getimagesize($file);
                    $rg= !(($fw==$new_w) && ($fh==$new_h));
                }
            }
        }
    }

    /*if(is_user_logged_in()){
        echo ("--$full_file--");
        echo ("--$file--");
        echo ("--$size--");
        echo ("--$sz_w X $sz_h--");
        echo ("--$fw X $fh--");
        var_dump($rg);
    }*/

    if($rg!=false){
        //echo "*";
        if(!function_exists("wp_generate_attachment_metadata")){
            require_once ABSPATH.'wp-admin/includes/admin.php';
        }
        if(function_exists("wp_generate_attachment_metadata")){
            $aux=wp_generate_attachment_metadata( $id, $full_file );
            debug("aux=".var_export($aux, true));
            $res=wp_update_attachment_metadata( $id, $aux );
            debug("res=$res(".false.")(".true.")");
        }
    }
    return false;
}

function _process_URL($img_url){
    preg_match('#(\w+://[^/]*)?(.*)/(.*)#', $img_url, $valores);
    list(,$server,$path,$img)=$valores;
    return array($server,$path,$img);
}
?>
