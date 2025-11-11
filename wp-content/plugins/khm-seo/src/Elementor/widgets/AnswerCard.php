<?php
/**
 * AnswerCard Widget
 *
 * Elementor widget for displaying answer cards with entity autocomplete.
 * Provides structured content display with automatic entity linking.
 *
 * @package KHM_SEO\Elementor\Widgets
 * @since 2.0.0
 */

namespace KHM_SEO\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * AnswerCard Widget Class
 */
class AnswerCard extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'khm-answer-card';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __( 'KHM Answer Card', 'khm-seo' );
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-info-box';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return array( 'khm-seo' );
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return array( 'answer', 'card', 'faq', 'entity', 'seo' );
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Tab
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __( 'Content', 'khm-seo' ),
                'tab' => Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'question',
            array(
                'label' => __( 'Question', 'khm-seo' ),
                'type' => Controls_Manager::TEXT,
                'default' => __( 'What is SEO?', 'khm-seo' ),
                'placeholder' => __( 'Enter your question', 'khm-seo' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'answer',
            array(
                'label' => __( 'Answer', 'khm-seo' ),
                'type' => Controls_Manager::WYSIWYG,
                'default' => __( 'SEO stands for Search Engine Optimization...', 'khm-seo' ),
                'placeholder' => __( 'Enter your answer', 'khm-seo' ),
            )
        );

        $this->add_control(
            'entity_id',
            array(
                'label' => __( 'Related Entity', 'khm-seo' ),
                'type' => 'khm_entity_autocomplete',
                'placeholder' => __( 'Search for an entity...', 'khm-seo' ),
                'description' => __( 'Link this answer card to a GEO entity for enhanced SEO', 'khm-seo' ),
            )
        );

        $this->add_control(
            'show_entity_link',
            array(
                'label' => __( 'Show Entity Link', 'khm-seo' ),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'condition' => array(
                    'entity_id!' => '',
                ),
            )
        );

        $this->add_control(
            'entity_link_text',
            array(
                'label' => __( 'Entity Link Text', 'khm-seo' ),
                'type' => Controls_Manager::TEXT,
                'default' => __( 'Learn more', 'khm-seo' ),
                'condition' => array(
                    'entity_id!' => '',
                    'show_entity_link' => 'yes',
                ),
            )
        );

        $this->end_controls_section();

        // Style Tab
        $this->start_controls_section(
            'style_section',
            array(
                'label' => __( 'Style', 'khm-seo' ),
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'card_background',
            array(
                'label' => __( 'Background Color', 'khm-seo' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .khm-answer-card' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'card_border',
                'selector' => '{{WRAPPER}} .khm-answer-card',
            )
        );

        $this->add_control(
            'card_border_radius',
            array(
                'label' => __( 'Border Radius', 'khm-seo' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors' => array(
                    '{{WRAPPER}} .khm-answer-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'card_box_shadow',
                'selector' => '{{WRAPPER}} .khm-answer-card',
            )
        );

        $this->add_control(
            'question_color',
            array(
                'label' => __( 'Question Color', 'khm-seo' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .khm-answer-card-question' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'question_typography',
                'selector' => '{{WRAPPER}} .khm-answer-card-question',
            )
        );

        $this->add_control(
            'answer_color',
            array(
                'label' => __( 'Answer Color', 'khm-seo' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .khm-answer-card-answer' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'answer_typography',
                'selector' => '{{WRAPPER}} .khm-answer-card-answer',
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        $entity_url = '';
        $entity_title = '';
        if ( ! empty( $settings['entity_id'] ) ) {
            $entity = khm_seo()->geo->get_entity_manager()->get_entity( $settings['entity_id'] );
            if ( $entity ) {
                $entity_url = get_permalink( $entity->id );
                $entity_title = $entity->canonical;
            }
        }

        $this->add_render_attribute( 'card', 'class', 'khm-answer-card' );
        $this->add_render_attribute( 'question', 'class', 'khm-answer-card-question' );
        $this->add_render_attribute( 'answer', 'class', 'khm-answer-card-answer' );

        ?>
        <div <?php echo $this->get_render_attribute_string( 'card' ); ?>>
            <?php if ( ! empty( $settings['question'] ) ) : ?>
                <h3 <?php echo $this->get_render_attribute_string( 'question' ); ?>>
                    <?php echo esc_html( $settings['question'] ); ?>
                </h3>
            <?php endif; ?>

            <?php if ( ! empty( $settings['answer'] ) ) : ?>
                <div <?php echo $this->get_render_attribute_string( 'answer' ); ?>>
                    <?php echo wp_kses_post( $settings['answer'] ); ?>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $entity_url ) && 'yes' === $settings['show_entity_link'] ) : ?>
                <div class="khm-answer-card-entity-link">
                    <a href="<?php echo esc_url( $entity_url ); ?>" class="khm-entity-link">
                        <?php echo esc_html( $settings['entity_link_text'] ); ?>
                        <span class="khm-entity-title"><?php echo esc_html( $entity_title ); ?></span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get widget script dependencies
     */
    public function get_script_depends() {
        return array( 'khm-geo-elementor-frontend' );
    }

    /**
     * Get widget style dependencies
     */
    public function get_style_depends() {
        return array( 'khm-geo-elementor-frontend' );
    }
}
