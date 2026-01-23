
## Concept

This package follows the following concept:
- setup and configuration is controlled by a config file
- manual edits have higher priority than automatics
- when manual edits would be overwritten there is a warning
- the documentation is organised out of components which get assembled at the end 
- important classes and methods are marked and documented
- badges are written from data 
- the build process is controlled by script
- missing files are explained with full path

## Universal Traits

Universal Traits provide a bridge between your package and the `packaging-tools` infrastructure. By using these traits, you benefit from:
- **Consistent DX:** Developers familiar with one package using these tools will feel at home with others.
- **Interoperability:** Traits automatically respect the project context (Laravel App vs. Package) and configuration.
- **Encapsulation:** Complex logic like model discovery or SQL loading is hidden behind simple, expressive method calls.


