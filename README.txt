
test




********
* DONE *
********


Fields
======

- refactoring kontroly default values, presunute do validate(), niekedy su pre kontrolu potrebne dalsie nastavenia,
  ktore este v momente nastavenia default hodnoty nemusia byt spracovane (DecimalField a jeho max_digits a decimal_places)
- prepracovanie nastavenia default hodnoty, pre DecimalField nastavuje ako '::double precision' co je pre float, nutne
  najskor prepracovat rovnako na NativeType

