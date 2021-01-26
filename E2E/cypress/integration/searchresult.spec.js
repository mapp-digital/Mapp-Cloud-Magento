// / <reference types="Cypress" />

describe('MappIntelligencePluginTests: Searchresult', () => {

    it('search result basic datalayer', () => {
        let data;
        cy.visit('/catalogsearch/result/?q=Argus')
        cy.window()
            .then((win) => {
                data = win._ti;
            })
            .then(() => {
                expect(data.pageName).to.equal('local.domain.com/catalogsearch/result/');
                expect(data.pageTitle).to.equal('Search results for: \'Argus\'');
                expect(data.contentCategory).to.equal('Catalogsearch');
                expect(data.contentSubcategory).to.equal('Result');
                expect(data.internalSearch).to.equal('Argus');
                expect(data.pageNumber).to.equal('1');
            });
    });

    it('search result basic datalayer page 2', () => {
        let data;
        cy.visit('/catalogsearch/result/index/?p=2&q=yoga')
        cy.window()
            .then((win) => {
                data = win._ti;
            })
            .then(() => {
                expect(data.pageName).to.equal('local.domain.com/catalogsearch/result/index/');
                expect(data.pageTitle).to.equal('Search results for: \'yoga\'');
                expect(data.contentCategory).to.equal('Catalogsearch');
                expect(data.contentSubcategory).to.equal('Result');
                expect(data.internalSearch).to.equal('yoga');
                expect(data.pageNumber).to.equal('2');
            });
    });
});
