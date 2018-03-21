# Code Guide Front-end Developement

### We use:

1. [SASS Pre-Processor](https://sass-lang.com/guide)
2. [BEM naming convention](http://getbem.com/naming/)
3. Kind of [SMACSS architecture](https://smacss.com/book/categorizing)
4. [Flex-box](https://flexbox.io/)

### We code :

1. Tab Length : 4
2. One empty line at the end of every files
3. We use variables & mixins as often as possible.
4. We use scss variable for every colors.

### Front Architecture

- style
    - base
        - _reset.scss
        - _general.scss
        - ...
    - layout
        - _header.scss
        - _footer.scss
        - ...        
    - pages
        - _home.scss
        - _contact.scss
        - ...        
    - module
        - _input.scss
        - _btn.scss
        - ...        
    - mixins
        - _font.scss
        - _icons.scss
        - ...        
    - vars
        - _colors.scss
        - ...  
    - vendors
        - _materialize.scss
    - app.scss
    
