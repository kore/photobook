# Photobook

Simple program to generate photo books out of photos specified by a simple YAML
file.

The program takes care of properly cutting, resizing the images. It offers
different templates for photo pages. Each template is a twig SVG file. All
pages will be concatenated and converted into a single PDF.

## Installation

It requires PHP 7. To setup all dependencies, run:

    composer install

## Usage

The program (only) has a CLI interface:

    ./bin/create <yamlFile>

