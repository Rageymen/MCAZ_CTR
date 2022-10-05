CREATE TRIGGER trg_refids
BEFORE INSERT ON `sdrugs`
FOR EACH ROW
BEGIN
      DECLARE quality_assessment_id INT;
      SELECT  COALESCE(MAX(refid), 0) + 1
      INTO    nrefid
      FROM    `references`       
      SET NEW.quality_assessment_id = quality_assessment_id;
END;


CREATE TRIGGER tgr_sdrugs
BEFORE INSERT ON `sdrugs` 
FOR EACH ROW
BEGIN
  SET NEW.quality_assessment_id = 
     (
       SELECT id 
         FROM quality_assessments
        WHERE created = NEW.created_at
     );
END;

 

