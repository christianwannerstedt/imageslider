<?php
/*
Plugin Name: Ikonhus House Menu Widget
Description: The sidebar menu for ikonhus houses
Author: Christian Wannerstedt @ Kloon Production AB
*/

class House_Menu_Widget extends WP_Widget {
  
  function House_Menu_Widget() {
    $widget_ops = array('classname' => 'House_Menu_Widget', 'description' => 'My Sample Widget Description');
    $this->WP_Widget('House_Menu_Widget', 'House Menu Widget', $widget_ops);
  }
 
  function form($instance){
    $instance = wp_parse_args((array) $instance, array( 'title' => '' ));
    $title = $instance['title'];
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
<?php
  }
 
  function update($new_instance, $old_instance){
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    return $instance;
  }
 
  function widget($args, $instance){
    extract($args, EXTR_SKIP);
 
    echo $before_widget;
    $title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
 
    if (!empty($title))
      echo $before_title . $title . $after_title;;
 
    // Do Your Widgety Stuff Here...
    echo "<h1>Hello World</h1>";
 
    echo $after_widget;
  }
}
add_action( 'widgets_init', create_function('', 'return register_widget("House_Menu_Widget");') );
?>