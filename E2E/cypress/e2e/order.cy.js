// / <reference types="Cypress" />

describe("Order Products", () => {
  beforeEach(() => {
    cy.intercept(/136699033798929\/wt\?p=/).as("trackRequest");
  });

  it("Order with multiple products", () => {

    cy.visit("/customer/account/login/");
    cy.wait("@trackRequest");
    cy.wait("@trackRequest");

    cy.get("#email").type("test@mapp.com");
    cy.get("#pass").type("Test1234!", {force: true});
    cy.get("#send2").click({force: true});
    cy.wait("@trackRequest");
    cy.wait("@trackRequest");

    cy.visit("/mapp-simple-product.html");
    cy.wait("@trackRequest");
    cy.wait("@trackRequest");
    cy.get("#qty").clear().type("5");
    cy.contains("Add to Cart").click();
    cy.wait("@trackRequest");
    cy.wait("@trackRequest");

    cy.visit("/mapp-configurable-product.html");
    cy.wait("@trackRequest");
    cy.wait("@trackRequest");
    cy.get(".super-attribute-select").select("green");
    cy.get("#qty").clear().type("6");
    cy.contains("Add to Cart").click();
    cy.wait("@trackRequest");
    cy.wait("@trackRequest");

    cy.visit("/mapp-bundle.html");
    cy.wait("@trackRequest");
    cy.wait("@trackRequest");
    cy.get("div.product-add-form").invoke("attr", "style", "display: block");
    cy.get("#bundle-option-1").select("Mapp Bundle Item 2 +€99.00");
    cy.get("#product-addtocart-button").click();
    cy.wait("@trackRequest");
    cy.wait("@trackRequest");

    cy.visit("checkout/#shipping");
    const expectationsForCheckoutPI = {
      5: (params) => {
        expect(params.cg1).to.equal("Checkout");
        expect(params.cp1).to.equal("Checkout");
        expect(params.fns).to.not.exist;
        expect(params.ba).to.not.exist;
        expect(params.ca1).to.not.exist;
        expect(params.ca2).to.not.exist;
        expect(params.ca3).to.not.exist;
        expect(params.co).to.not.exist;
        expect(params.ct).to.not.exist;
        expect(params.st).to.not.exist;
        expect(params.qn).to.not.exist;
        expect(params.eid).to.match(/^2\d{18}$/);
        expect(params.cd).to.match(/^[a-z0-9]{64}$/);
      },
      6: (params) => {
        expect(params.cg1).to.equal("Checkout");
        expect(params.fns).to.not.exist;
        expect(params.ba).to.not.exist;
        expect(params.ca1).to.not.exist;
        expect(params.ca2).to.not.exist;
        expect(params.ca3).to.not.exist;
        expect(params.co).to.not.exist;
        expect(params.ct).to.not.exist;
        expect(params.st).to.not.exist;
        expect(params.qn).to.not.exist;
        expect(params.eid).to.match(/^2\d{18}$/);
        expect(params.cd).to.match(/^[a-z0-9]{64}$/);
      },
    };
    cy.testTrackRequest("@trackRequest").then((trackRequest) => {
      expectationsForCheckoutPI[trackRequest.version](trackRequest.params);
    });
    cy.testTrackRequest("@trackRequest").then((trackRequest) => {
      expectationsForCheckoutPI[trackRequest.version](trackRequest.params);
    });

    const expectationsForOrderPI = {
      5: (params) => {
        expect(params.cg1).to.equal("Checkout");
        expect(params.cg2).to.equal("Onepage");
        expect(params.cp1).to.equal("Checkout");
        expect(params.fns).to.not.exist;
        expect(params.ba).to.equal("1;2;8");
        expect(params.ca1).to.equal("MAPP main category;MAPP main category;MAPP main category");
        expect(params.ca2).to.equal("MAPP sub category;MAPP sub category;MAPP sub category");
        expect(params.ca3).to.equal(
          "Mapp Simple Product;Mapp Configurable Product;Mapp Bundle"
        );
        expect(params.co).to.equal("350;138;99");
        expect(params.ct).to.not.exist;
        expect(params.st).to.equal("conf");
        expect(params.qn).to.equal("5;6;1");
        expect(params.cr).to.equal("EUR");
        expect(params.ov).to.equal("647");
        expect(params.eid).to.match(/^2\d{18}$/);
        expect(params.cd).to.match(/^[a-z0-9]{64}$/);
        expect(params.oi).to.match(/^[0-9]{9}$/);
      },
      6: (params) => {
        expect(params.cg1).to.equal("Checkout");
        expect(params.cg2).to.equal("Onepage");
        expect(params.fns).to.not.exist;
        expect(params.ba).to.equal("1;2;8");
        expect(params.ca1).to.equal("MAPP main category;MAPP main category;MAPP main category");
        expect(params.ca2).to.equal("MAPP sub category;MAPP sub category;MAPP sub category");
        expect(params.ca3).to.equal(
          "Mapp Simple Product;Mapp Configurable Product;Mapp Bundle"
        );
        expect(params.co).to.equal("350;138;99");
        expect(params.ct).to.not.exist;
        expect(params.st).to.equal("conf");
        expect(params.qn).to.equal("5;6;1");
        expect(params.cr).to.equal("EUR");
        expect(params.ov).to.equal("647");
        expect(params.eid).to.match(/^2\d{18}$/);
        expect(params.cd).to.match(/^[a-z0-9]{64}$/);
        expect(params.oi).to.match(/^[0-9]{9}$/);
      },
    };

    cy.get('button[data-role="opc-continue"]').click();
    cy.get(".action.primary.checkout").click();

    cy.testTrackRequest("@trackRequest").then((trackRequest) => {
      expectationsForOrderPI[trackRequest.version](trackRequest.params);
    });
    cy.testTrackRequest("@trackRequest").then((trackRequest) => {
      expectationsForOrderPI[trackRequest.version](trackRequest.params);
    });
  });
});


  