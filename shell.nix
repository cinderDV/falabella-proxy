{ pkgs ? import <nixpkgs> {} }:

pkgs.mkShell {
  buildInputs = [
    pkgs.php83
    pkgs.php83Packages.composer
  ];

  shellHook = ''
    echo "--- Entorno PHP Minimalista ---"
    php -v
    echo "-------------------------------"
  '';
}